<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use RuntimeException;
class Spinner extends Prompt
{
    /**
     * How long to wait between rendering each frame.
     */
    public int $interval = 100;
    /**
     * The number of times the spinner has been rendered.
     */
    public int $count = 0;
    /**
     * Whether the spinner can only be rendered once.
     */
    public bool $static = false;
    /**
     * The process ID after forking.
     */
    protected int $pid;
    /**
     * Create a new Spinner instance.
     */
    public function __construct(public string $message = '')
    {
    }
    /**
     * Render the spinner and execute the callback.
     *
     * @template TReturn of mixed
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function spin(Closure $callback): mixed
    {
        $this->capture_previous_new_lines();
        if (!function_exists('pcntl_fork')) {
            return $this->render_statically($callback);
        }
        $original_async = pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn() => exit);
        try {
            $this->hide_cursor();
            $this->render();
            $this->pid = pcntl_fork();
            if ($this->pid === 0) {
                while (true) {
                    // @phpstan-ignore-line
                    $this->render();
                    $this->count++;
                    usleep($this->interval * 1000);
                }
            } else {
                $result = $callback();
                $this->reset_terminal($original_async);
                return $result;
            }
        } catch (\Throwable $e) {
            $this->reset_terminal($original_async);
            throw $e;
        }
    }
    /**
     * Reset the terminal.
     */
    protected function reset_terminal(bool $original_async): void
    {
        pcntl_async_signals($original_async);
        pcntl_signal(SIGINT, SIG_DFL);
        $this->erase_rendered_lines();
    }
    /**
     * Render a static version of the spinner.
     *
     * @template TReturn of mixed
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function render_statically(Closure $callback): mixed
    {
        $this->static = true;
        try {
            $this->hide_cursor();
            $this->render();
            $result = $callback();
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
        throw new RuntimeException('Spinner cannot be prompted.');
    }
    /**
     * Get the current value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
    /**
     * Clear the lines rendered by the spinner.
     */
    protected function erase_rendered_lines(): void
    {
        $lines = explode(PHP_EOL, $this->prev_frame);
        $this->move_cursor(-999, -count($lines) + 1);
        $this->erase_down();
    }
    /**
     * Clean up after the spinner.
     */
    public function __destruct()
    {
        if (!empty($this->pid)) {
            posix_kill($this->pid, SIGHUP);
        }
        parent::__destruct();
    }
}