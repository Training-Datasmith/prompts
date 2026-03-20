<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Terminal as SymfonyTerminal;
class Terminal
{
    /**
     * The initial TTY mode.
     */
    protected ?string $initial_tty_mode = null;
    /**
     * Whether the terminal supports true color.
     */
    protected static ?bool $true_color_support = null;
    /**
     * The terminal's foreground color as an RGB array.
     *
     * @var array{int, int, int}|null
     */
    protected static ?array $foreground_color = null;
    /**
     * The terminal's background color as an RGB array.
     *
     * @var array{int, int, int}|null
     */
    protected static ?array $background_color = null;
    /**
     * The Symfony Terminal instance.
     */
    protected Symfony_Terminal $terminal;
    /**
     * Create a new Terminal instance.
     */
    public function __construct()
    {
        $this->terminal = new Symfony_Terminal();
    }
    /**
     * Read a line from the terminal.
     */
    public function read(): string
    {
        $input = fread(STDIN, 1024);
        return $input !== false ? $input : '';
    }
    /**
     * Set the TTY mode.
     */
    public function set_tty(string $mode): void
    {
        $this->initial_tty_mode ??= $this->exec('stty -g');
        $this->exec("stty {$mode}");
    }
    /**
     * Restore the initial TTY mode.
     */
    public function restore_tty(): void
    {
        if (isset($this->initial_tty_mode)) {
            $this->exec("stty {$this->initial_tty_mode}");
            $this->initial_tty_mode = null;
        }
    }
    /**
     * Get the number of columns in the terminal.
     */
    public function cols(): int
    {
        return $this->terminal->get_width();
    }
    /**
     * Get the number of lines in the terminal.
     */
    public function lines(): int
    {
        return $this->terminal->get_height();
    }
    /**
     * (Re)initialize the terminal dimensions.
     */
    public function init_dimensions(): void
    {
        (new ReflectionClass($this->terminal))->get_method('initDimensions')->invoke($this->terminal);
    }
    /**
     * Exit the interactive session.
     */
    public function exit(): void
    {
        exit(1);
    }
    /**
     * Execute the given command and return the output.
     */
    protected function exec(string $command): string
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!$process) {
            throw new RuntimeException('Failed to create process.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        $code = proc_close($process);
        if ($code !== 0 || $stdout === false) {
            throw new RuntimeException(trim($stderr ?: "Unknown error (code: {$code})"), $code);
        }
        return $stdout;
    }
    /**
     * Determine if the terminal supports true color (24-bit).
     */
    public function supports_true_color(): bool
    {
        return static::$true_color_support ??= in_array(getenv('COLORTERM'), ['truecolor', '24bit']);
    }
    /**
     * Get the terminal's foreground color as an RGB array.
     *
     * @return array{int, int, int}
     */
    public function foreground_color(): array
    {
        if (static::$foreground_color === null) {
            $this->query_colors();
        }
        return static::$foreground_color;
    }
    /**
     * Get the terminal's background color as an RGB array.
     *
     * @return array{int, int, int}
     */
    public function background_color(): array
    {
        if (static::$background_color === null) {
            $this->query_colors();
        }
        return static::$background_color;
    }
    /**
     * Query the terminal for foreground and background colors in a single shot.
     */
    protected function query_colors(): void
    {
        $saved_stty = trim((string) shell_exec('stty -g < /dev/tty'));
        shell_exec('stty raw -echo min 0 time 1 < /dev/tty');
        fwrite(STDOUT, "\x1b]10;?\x1b\\\x1b]11;?\x1b\\");
        fflush(STDOUT);
        $tty_in = fopen('/dev/tty', 'r');
        if ($tty_in === false) {
            static::$foreground_color = [204, 204, 204];
            static::$background_color = [0, 0, 0];
            return;
        }
        $response = fread($tty_in, 200);
        fclose($tty_in);
        shell_exec("stty {$saved_stty} < /dev/tty");
        preg_match_all('/rgb:([0-9a-f]+)\/([0-9a-f]+)\/([0-9a-f]+)/i', $response ?: '', $matches, PREG_SET_ORDER);
        $parse = fn(array $m): array => [(int) (hexdec((string) $m[1]) / (strlen((string) $m[1]) === 4 ? 257 : 1)), (int) (hexdec((string) $m[2]) / (strlen((string) $m[2]) === 4 ? 257 : 1)), (int) (hexdec((string) $m[3]) / (strlen((string) $m[3]) === 4 ? 257 : 1))];
        static::$foreground_color = isset($matches[0]) ? $parse($matches[0]) : [204, 204, 204];
        static::$background_color = isset($matches[1]) ? $parse($matches[1]) : [0, 0, 0];
    }
}