<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

use Intl_Break_Iterator;
use Laravel\Prompts\Key;
trait Typed_Value
{
    /**
     * The value that has been typed.
     */
    protected string $typed_value = '';
    /**
     * The position of the virtual cursor.
     */
    protected int $cursor_position = 0;
    /**
     * Track the value as the user types.
     */
    protected function track_typed_value(string $default = '', bool $submit = true, ?callable $ignore = null, bool $allow_new_line = false): void
    {
        $this->typed_value = $default;
        if (strlen($this->typed_value) > 0) {
            $this->cursor_position = mb_strlen($this->typed_value);
        }
        $this->on('key', function (string $key) use ($submit, $ignore, $allow_new_line): void {
            if ($key !== '' && ($key[0] === "\x1b" || in_array($key, [Key::CTRL_B, Key::CTRL_F, Key::CTRL_A, Key::CTRL_E]))) {
                if ($ignore !== null && $ignore($key)) {
                    return;
                }
                match ($key) {
                    Key::LEFT, Key::LEFT_ARROW, Key::CTRL_B => $this->cursor_position = max(0, $this->cursor_position - 1),
                    Key::RIGHT, Key::RIGHT_ARROW, Key::CTRL_F => $this->cursor_position = min(mb_strlen($this->typed_value), $this->cursor_position + 1),
                    Key::one_of([Key::HOME, Key::CTRL_A], $key) => $this->cursor_position = 0,
                    Key::one_of([Key::END, Key::CTRL_E], $key) => $this->cursor_position = mb_strlen($this->typed_value),
                    Key::DELETE => $this->typed_value = mb_substr($this->typed_value, 0, $this->cursor_position) . mb_substr($this->typed_value, $this->cursor_position + 1),
                    Key::OPTION_BACKSPACE => $this->delete_word_backward(),
                    default => null,
                };
                return;
            }
            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if ($ignore !== null && $ignore($key)) {
                    return;
                }
                if ($key === Key::ENTER) {
                    if ($submit) {
                        $this->submit();
                        return;
                    }
                    if ($allow_new_line) {
                        $this->typed_value = mb_substr($this->typed_value, 0, $this->cursor_position) . PHP_EOL . mb_substr($this->typed_value, $this->cursor_position);
                        $this->cursor_position++;
                    }
                } elseif ($key === Key::BACKSPACE || $key === Key::CTRL_H) {
                    if ($this->cursor_position === 0) {
                        return;
                    }
                    $this->typed_value = mb_substr($this->typed_value, 0, $this->cursor_position - 1) . mb_substr($this->typed_value, $this->cursor_position);
                    $this->cursor_position--;
                } elseif (mb_ord($key) >= 32) {
                    $this->typed_value = mb_substr($this->typed_value, 0, $this->cursor_position) . $key . mb_substr($this->typed_value, $this->cursor_position);
                    $this->cursor_position++;
                }
            }
        });
    }
    /**
     * Get the value of the prompt.
     */
    public function value(): string
    {
        return $this->typed_value;
    }
    /**
     * Add a virtual cursor to the value and truncate if necessary.
     */
    protected function add_cursor(string $value, int $cursor_position, ?int $max_width = null): string
    {
        $before = mb_substr($value, 0, $cursor_position);
        $current = mb_substr($value, $cursor_position, 1);
        $after = mb_substr($value, $cursor_position + 1);
        $cursor = mb_strlen($current) && $current !== PHP_EOL ? $current : ' ';
        $space_before = $max_width < 0 || $max_width === null ? mb_strwidth($before) : $max_width - mb_strwidth($cursor) - (mb_strwidth($after) > 0 ? 1 : 0);
        [$truncated_before, $was_truncated_before] = mb_strwidth($before) > $space_before ? [$this->trim_width_backwards($before, 0, $space_before - 1), true] : [$before, false];
        $space_after = $max_width < 0 || $max_width === null ? mb_strwidth($after) : $max_width - ($was_truncated_before ? 1 : 0) - mb_strwidth((string) $truncated_before) - mb_strwidth($cursor);
        [$truncated_after, $was_truncated_after] = mb_strwidth($after) > $space_after ? [mb_strimwidth($after, 0, $space_after - 1), true] : [$after, false];
        return ($was_truncated_before ? $this->dim('…') : '') . $truncated_before . $this->inverse($cursor) . ($current === PHP_EOL ? PHP_EOL : '') . $truncated_after . ($was_truncated_after ? $this->dim('…') : '');
    }
    /**
     * Get a truncated string with the specified width from the end.
     */
    private function trim_width_backwards(string $string, int $start, int $width): string
    {
        $reversed = implode('', array_reverse(mb_str_split($string, 1)));
        $trimmed = mb_strimwidth($reversed, $start, $width);
        return implode('', array_reverse(mb_str_split($trimmed, 1)));
    }
    /**
     * Delete from the start of the current word (before cursor) through the cursor.
     */
    protected function delete_word_backward(): void
    {
        if ($this->cursor_position === 0) {
            return;
        }
        $start = $this->find_word_start_before_cursor();
        $this->typed_value = mb_substr($this->typed_value, 0, $start) . mb_substr($this->typed_value, $this->cursor_position);
        $this->cursor_position = $start;
    }
    /**
     * Character offset of the word boundary immediately before the cursor (Intl + punctuation).
     * Punctuation (e.g. . - _) is treated as a word boundary so "word.word" deletes in two steps.
     */
    protected function find_word_start_before_cursor(): int
    {
        $before = mb_substr($this->typed_value, 0, $this->cursor_position);
        if ($before === '') {
            return 0;
        }
        $regex_start = $this->find_last_word_start_by_letters_and_numbers($before);
        if (extension_loaded('intl')) {
            $iterator = Intl_Break_Iterator::create_word_instance('');
            $iterator->set_text($before);
            $end_byte = strlen($before);
            $word_start_byte = $iterator->preceding($end_byte);
            if ($word_start_byte === Intl_Break_Iterator::DONE) {
                return $regex_start;
            }
            $intl_start = mb_strlen(substr($before, 0, $word_start_byte), 'UTF-8');
            return max($intl_start, $regex_start);
        }
        return $regex_start;
    }
    /**
     * Start (character offset) of the last run of letters/numbers in string (punctuation breaks words).
     */
    protected function find_last_word_start_by_letters_and_numbers(string $before): int
    {
        if (preg_match_all('/((?:\p{L}\p{M}*|\p{N})+)/u', $before, $m, PREG_OFFSET_CAPTURE) && $m[1] !== []) {
            $last = end($m[1]);
            return mb_strlen(substr($before, 0, $last[1]), 'UTF-8');
        }
        return 0;
    }
}