<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Laravel\Prompts\Themes\Default\Concerns\Interacts_With_Strings;
class Stream extends Prompt
{
    use Interacts_With_Strings;
    protected int $min_width = 0;
    protected string $message = '';
    /** @var array<int, string> */
    protected array $currently_fading = [];
    protected int $max_width = 0;
    /** @var array<int, \Closure(string): string> */
    protected array $fading_out_colors = [];
    /**
     * Create a new Stream instance.
     */
    public function __construct()
    {
        $this->max_width = static::terminal()->cols() - 20;
        $this->hide_cursor();
        $this->fading_out_colors = $this->fade_out();
    }
    public function append(string $message): self
    {
        $this->currently_fading[] = $message;
        while (count($this->currently_fading) > count($this->fading_out_colors)) {
            $this->message .= array_shift($this->currently_fading);
        }
        $this->render();
        return $this;
    }
    public function close(): void
    {
        try {
            while (count($this->currently_fading) > 0) {
                $this->message .= array_shift($this->currently_fading);
                $this->render();
                usleep(25000);
            }
        } finally {
            $this->show_cursor();
        }
    }
    /** @return array<int, string> */
    public function lines(): array
    {
        $to_fade_in = [];
        foreach ($this->currently_fading as $index => $message) {
            $to_fade_in[] = $this->fading_out_colors[$index]($message);
        }
        $lines = explode(PHP_EOL, $this->message . implode('', $to_fade_in));
        $final_lines = [];
        foreach ($lines as $line) {
            $final_lines = array_merge($final_lines, $this->ansi_wordwrap($line, $this->max_width));
        }
        return $final_lines;
    }
    public function prompt(): mixed
    {
        throw new \RuntimeException('Stream cannot be prompted');
    }
    /**
     * Get the value of the prompt.
     */
    public function value(): string
    {
        return $this->message . implode('', $this->currently_fading);
    }
    /**
     * Get an array of closures that progressively fade text from full color to nearly invisible.
     *
     * @return array<int, \Closure(string): string>
     */
    protected function fade_out(int $steps = 10): array
    {
        if (!static::terminal()->supports_true_color()) {
            return [fn(string $text): string => $text, $this->dim(...)];
        }
        $fg = static::terminal()->foreground_color();
        $bg = static::terminal()->background_color();
        return array_map(function (int $step) use ($fg, $bg, $steps): \Closure {
            $factor = 1 - $step / $steps;
            $r = (int) ($bg[0] + ($fg[0] - $bg[0]) * $factor);
            $g = (int) ($bg[1] + ($fg[1] - $bg[1]) * $factor);
            $b = (int) ($bg[2] + ($fg[2] - $bg[2]) * $factor);
            return fn(string $text): string => "\x1b[38;2;{$r};{$g};{$b}m{$text}\x1b[0m";
        }, range(0, $steps - 1));
    }
}