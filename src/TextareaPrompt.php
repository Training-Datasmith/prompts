<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Laravel\Prompts\Support\Utils;
use Laravel\Prompts\Themes\Default\Concerns\Interacts_With_Strings;
class Textarea_Prompt extends Prompt
{
    use Concerns\Scrolling;
    use Concerns\Typed_Value;
    use Interacts_With_Strings;
    protected int $min_width = 0;
    /**
     * The width of the textarea.
     */
    public int $width = 60;
    /**
     * Create a new TextareaPrompt instance.
     */
    public function __construct(public string $label, public string $placeholder = '', public string $default = '', public bool|string $required = false, public mixed $validate = null, public string $hint = '', int $rows = 5, public ?Closure $transform = null)
    {
        $this->scroll = $rows;
        $this->initialize_scrolling();
        $this->track_typed_value(default: $default, submit: false, allowNewLine: true);
        $this->on('key', function ($key): void {
            if ($key[0] === "\x1b") {
                match ($key) {
                    Key::UP, Key::UP_ARROW, Key::CTRL_P => $this->handle_up_key(),
                    Key::DOWN, Key::DOWN_ARROW, Key::CTRL_N => $this->handle_down_key(),
                    default => null,
                };
                return;
            }
            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if ($key === Key::CTRL_D) {
                    $this->submit();
                    return;
                }
            }
        });
    }
    /**
     * Get the formatted value with a virtual cursor.
     */
    public function value_with_cursor(): string
    {
        if ($this->value() === '') {
            return $this->wrapped_placeholder_with_cursor();
        }
        return $this->add_cursor($this->wrapped_value(), $this->cursor_position + $this->cursor_offset(), -1);
    }
    /**
     * The word-wrapped version of the typed value.
     */
    public function wrapped_value(): string
    {
        return $this->mb_wordwrap($this->value(), $this->width, PHP_EOL, true);
    }
    /**
     * The formatted lines.
     *
     * @return array<int, string>
     */
    public function lines(): array
    {
        return explode(PHP_EOL, $this->wrapped_value());
    }
    /**
     * The currently visible lines.
     *
     * @return array<int, string>
     */
    public function visible(): array
    {
        $this->adjust_visible_window();
        $with_cursor = $this->value_with_cursor();
        return array_slice(explode(PHP_EOL, $with_cursor), $this->first_visible, $this->scroll, preserve_keys: true);
    }
    /**
     * Handle the up key press.
     */
    protected function handle_up_key(): void
    {
        if ($this->cursor_position === 0) {
            return;
        }
        $lines = $this->lines();
        // Line length + 1 for the newline character
        $line_lengths = array_map(fn($line, $index): int => mb_strlen((string) $line) + ($index === count($lines) - 1 ? 0 : 1), $lines, range(0, count($lines) - 1));
        $current_line_index = $this->current_line_index();
        if ($current_line_index === 0) {
            // They're already at the first line, jump them to the first position
            $this->cursor_position = 0;
            return;
        }
        $current_lines = array_slice($line_lengths, 0, $current_line_index + 1);
        $current_column = Utils::last($current_lines) - (array_sum($current_lines) - $this->cursor_position);
        $destination_line_length = ($line_lengths[$current_line_index - 1] ?? $current_lines[0]) - 1;
        $new_column = min($destination_line_length, $current_column);
        $full_lines = array_slice($current_lines, 0, -2);
        $this->cursor_position = array_sum($full_lines) + $new_column;
    }
    /**
     * Handle the down key press.
     */
    protected function handle_down_key(): void
    {
        $lines = $this->lines();
        // Line length + 1 for the newline character
        $line_lengths = array_map(fn($line, $index): int => mb_strlen((string) $line) + ($index === count($lines) - 1 ? 0 : 1), $lines, range(0, count($lines) - 1));
        $current_line_index = $this->current_line_index();
        if ($current_line_index === count($lines) - 1) {
            // They're already at the last line, jump them to the last position
            $this->cursor_position = mb_strlen(implode(PHP_EOL, $lines));
            return;
        }
        // Lines up to and including the current line
        $current_lines = array_slice($line_lengths, 0, $current_line_index + 1);
        $current_column = Utils::last($current_lines) - (array_sum($current_lines) - $this->cursor_position);
        $destination_line_length = $line_lengths[$current_line_index + 1] ?? Utils::last($current_lines);
        if ($current_line_index + 1 !== count($lines) - 1) {
            $destination_line_length--;
        }
        $new_column = min(max(0, $destination_line_length), $current_column);
        $this->cursor_position = array_sum($current_lines) + $new_column;
    }
    /**
     * Adjust the visible window to ensure the cursor is always visible.
     */
    protected function adjust_visible_window(): void
    {
        if (count($this->lines()) < $this->scroll) {
            return;
        }
        $current_line_index = $this->current_line_index();
        while ($this->first_visible + $this->scroll <= $current_line_index) {
            $this->first_visible++;
        }
        if ($current_line_index === $this->first_visible - 1) {
            $this->first_visible = max(0, $this->first_visible - 1);
        }
        // Make sure there are always the scroll amount visible
        if ($this->first_visible + $this->scroll > count($this->lines())) {
            $this->first_visible = count($this->lines()) - $this->scroll;
        }
    }
    /**
     * Get the index of the current line that the cursor is on.
     */
    protected function current_line_index(): int
    {
        $total_line_length = 0;
        return (int) Utils::search($this->lines(), function ($line) use (&$total_line_length): bool {
            $total_line_length += mb_strlen($line) + 1;
            return $total_line_length > $this->cursor_position;
        }) ?: 0;
    }
    /**
     * Calculate the cursor offset considering wrapped words.
     */
    protected function cursor_offset(): int
    {
        $cursor_offset = 0;
        preg_match_all('/\S{' . $this->width . ',}/u', $this->value(), $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            if ($this->cursor_position + $cursor_offset >= $match[1] + mb_strwidth($match[0])) {
                $cursor_offset += (int) floor(mb_strwidth($match[0]) / $this->width);
            }
        }
        return $cursor_offset;
    }
    /**
     * A wrapped version of the placeholder with the virtual cursor.
     */
    protected function wrapped_placeholder_with_cursor(): string
    {
        return implode(PHP_EOL, array_map($this->dim(...), explode(PHP_EOL, $this->add_cursor($this->mb_wordwrap($this->placeholder, $this->width, PHP_EOL, true), cursorPosition: 0))));
    }
}