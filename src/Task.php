<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Laravel\Prompts\Support\Logger;
use Laravel\Prompts\Themes\Default\Concerns\Interacts_With_Strings;
use RuntimeException;
class Task extends Prompt
{
    use Interacts_With_Strings;
    /**
     * The minimum width for the longest line calculation.
     */
    protected int $min_width = 0;
    /**
     * How long to wait between rendering each frame.
     */
    public int $interval = 100;
    /**
     * The number of times the task has been rendered.
     */
    public int $count = 0;
    /**
     * Whether the task can only be rendered once.
     */
    public bool $static = false;
    /**
     * The process ID after forking.
     */
    protected int $pid;
    /**
     * The socket for IPC communication.
     *
     * @var resource|null
     */
    protected $socket;
    /**
     * Pre-wrapped log lines for the scrolling output area.
     *
     * @var array<int, string>
     */
    public array $logs = [];
    /**
     * Stable status messages (success, warning, error).
     *
     * @var list<array{type: string, message: string}>
     */
    public array $stable_messages = [];
    /**
     * The maximum number of stable messages to display.
     */
    public int $max_stable_messages = 10;
    /**
     * The identifier for the task.
     */
    public string $identifier = '';
    /**
     * Whether the task has finished.
     */
    public bool $finished = false;
    /**
     * Buffer for incomplete lines from non-blocking socket reads.
     */
    protected string $buffer = '';
    /**
     * The log index where the current partial started, or null if not streaming.
     */
    protected ?int $partial_start_index = null;
    /**
     * Create a new Task instance.
     */
    public function __construct(public string $label = '', public int $limit = 10)
    {
        $this->identifier = uniqid();
    }
    /**
     * Render the task and execute the callback.
     *
     * @template TReturn of mixed
     *
     * @param  Closure(Logger): TReturn  $callback
     * @return TReturn
     */
    public function run(Closure $callback): mixed
    {
        $max_height = $this->terminal()->lines() - 10;
        $this->limit = min($this->limit, $max_height);
        // Max height - limit - divider - task label
        $this->max_stable_messages = max(0, $max_height - $this->limit - 2);
        $this->capture_previous_new_lines();
        if (!function_exists('pcntl_fork')) {
            return $this->render_statically($callback);
        }
        $original_async = pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn() => exit);
        try {
            $this->hide_cursor();
            $this->render();
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            if ($sockets === false) {
                return $this->render_statically($callback);
            }
            $this->pid = pcntl_fork();
            if ($this->pid === 0) {
                fclose($sockets[1]);
                $child_socket = $sockets[0];
                stream_set_blocking($child_socket, false);
                while (true) {
                    // @phpstan-ignore-line
                    $this->receive_messages($child_socket);
                    if (!$this->finished) {
                        $this->render();
                        $this->count++;
                    }
                    usleep($this->interval * 1000);
                }
            } else {
                fclose($sockets[0]);
                $this->socket = $sockets[1];
                $logger = new Logger($this->identifier, $this->socket);
                $result = $callback($logger);
                if ($this->socket !== null) {
                    // Send a reset message to the parent process to reset the terminal.
                    fwrite($this->socket, $this->identifier . '_' . 'reset:' . ($original_async ? 1 : 0) . PHP_EOL);
                    usleep($this->interval * 2000);
                }
                return $result;
            }
        } catch (\Throwable $e) {
            $this->reset_terminal($original_async);
            throw $e;
        }
    }
    /**
     * Receive and process messages from the parent process.
     *
     * @param  resource  $socket
     */
    protected function receive_messages($socket): void
    {
        $prefix = preg_quote($this->identifier, '/');
        while (($data = fgets($socket)) !== false) {
            // Buffer incomplete lines from non-blocking reads.
            if (!str_ends_with($data, PHP_EOL)) {
                $this->buffer .= $data;
                continue;
            }
            $line = rtrim($this->buffer . $data, PHP_EOL);
            $this->buffer = '';
            if ($line === '') {
                continue;
            }
            // Check for typed messages: {id}_{type}:{content}
            if (preg_match('/^' . $prefix . '_(success|warning|error|label|reset|partial|commitpartial):(.*)/', $line, $matches)) {
                $type = $matches[1];
                $content = $matches[2];
                if ($type === 'reset') {
                    $this->reset_terminal((bool) $content);
                    continue;
                }
                if ($type === 'partial') {
                    $this->replace_partial_lines($content);
                    continue;
                }
                if ($type === 'commitpartial') {
                    $this->partial_start_index = null;
                    continue;
                }
                if ($type === 'label') {
                    $this->label = $content;
                } else {
                    $this->stable_messages[] = ['type' => $type, 'message' => $content];
                    $this->logs = [];
                    $this->partial_start_index = null;
                }
                while (count($this->stable_messages) > $this->max_stable_messages) {
                    array_shift($this->stable_messages);
                }
                continue;
            }
            // Regular log line — strip cursor-reset control sequences.
            $line = preg_replace('/\e\[(?:1)?G\e\[2K/', '', $line);
            // Wrap and add to ring buffer.
            $this->add_log_lines($line);
        }
    }
    /**
     * Wrap a log line and append to the ring buffer, trimming to the limit.
     */
    protected function add_log_lines(string $line): void
    {
        $width = $this->terminal()->cols() - 10;
        $plain_text = $this->strip_escape_sequences($line);
        if (mb_strwidth($plain_text) > $width) {
            $wrapped = $this->ansi_wordwrap($line, $width);
        } else {
            $wrapped = [$line];
        }
        array_push($this->logs, ...$wrapped);
        while (count($this->logs) > $this->limit) {
            array_shift($this->logs);
        }
    }
    /**
     * Replace the in-progress partial lines with the full accumulated text.
     */
    protected function replace_partial_lines(string $text): void
    {
        if ($this->partial_start_index === null) {
            $this->partial_start_index = count($this->logs);
        }
        // Truncate back to where the partial started.
        $this->logs = array_slice($this->logs, 0, $this->partial_start_index);
        // Wrap and append the full accumulated partial text.
        $width = $this->terminal()->cols() - 10;
        $plain_text = $this->strip_escape_sequences($text);
        if (mb_strwidth($plain_text) > $width) {
            $wrapped = $this->ansi_wordwrap($text, $width);
        } else {
            $wrapped = [$text];
        }
        array_push($this->logs, ...$wrapped);
        while (count($this->logs) > $this->limit) {
            array_shift($this->logs);
            $this->partial_start_index = max(0, $this->partial_start_index - 1);
        }
    }
    /**
     * Reset the terminal.
     */
    protected function reset_terminal(bool $original_async): void
    {
        $this->finished = true;
        pcntl_async_signals($original_async);
        pcntl_signal(SIGINT, SIG_DFL);
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->erase_rendered_lines();
    }
    /**
     * Render a static version of the task.
     *
     * @template TReturn of mixed
     *
     * @param  Closure(Logger): TReturn  $callback
     * @return TReturn
     */
    protected function render_statically(Closure $callback): mixed
    {
        $this->static = true;
        try {
            $this->hide_cursor();
            $this->render();
            $logger = new Logger($this->identifier);
            $result = $callback($logger);
        } finally {
            $this->erase_rendered_lines();
        }
        return $result;
    }
    /**
     * Disable prompting for input.
     *
     * @throws RuntimeException
     */
    public function prompt(): never
    {
        throw new RuntimeException('Task cannot be prompted.');
    }
    /**
     * Get the current value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
    /**
     * Clear the lines rendered by the task.
     */
    protected function erase_rendered_lines(): void
    {
        $lines = explode(PHP_EOL, $this->prev_frame);
        $this->move_cursor(-999, -count($lines) + 1);
        $this->erase_down();
    }
    /**
     * Clean up after the task.
     */
    public function __destruct()
    {
        if (!empty($this->pid)) {
            posix_kill($this->pid, SIGHUP);
        }
        parent::__destruct();
    }
}