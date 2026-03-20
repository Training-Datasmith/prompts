<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use RuntimeException;
class Number_Prompt extends Prompt
{
    use Concerns\Typed_Value;
    /**
     * Create a new NumberPrompt instance.
     */
    public function __construct(public string $label, public string $placeholder = '', public string $default = '', public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null, public ?int $min = null, public ?int $max = null, public ?int $step = null)
    {
        $this->track_typed_value($default);
        $this->step = max(1, $this->step ?? 1);
        $this->min ??= PHP_INT_MIN;
        $this->max ??= PHP_INT_MAX;
        $this->validate = $this->wrap_validation($this->validate);
        $this->on('key', function (string $key): void {
            match ($key) {
                Key::UP, Key::UP_ARROW => $this->increase_value(),
                Key::DOWN, Key::DOWN_ARROW => $this->decrease_value(),
                default => null,
            };
        });
    }
    protected function wrap_validation(mixed $validate): callable
    {
        return function ($value) use ($validate) {
            if ($value !== '' && !is_numeric($value)) {
                return 'Must be a number';
            }
            if (is_numeric($value)) {
                if ($value < $this->min) {
                    return 'Must be at least ' . $this->min;
                }
                if ($value > $this->max) {
                    return 'Must be less than ' . $this->max;
                }
            }
            if (!$validate && !isset(static::$validate_using)) {
                return null;
            }
            return match (true) {
                is_callable($validate) => $validate($value),
                isset(static::$validate_using) => (static::$validate_using)($this),
                default => throw new RuntimeException('The validation logic is missing.'),
            };
        };
    }
    /**
     * Increase the value of the prompt by the step.
     */
    protected function increase_value(): void
    {
        if ($this->typed_value === '') {
            $this->typed_value = (string) ($this->min === PHP_INT_MIN ? 1 : $this->min);
            $this->cursor_position++;
            return;
        }
        if (is_numeric($this->typed_value)) {
            $previous_value_length = mb_strlen($this->typed_value);
            $this->typed_value = (string) min($this->max, (int) $this->typed_value + $this->step);
            if (mb_strlen($this->typed_value) > $previous_value_length) {
                $this->cursor_position++;
            }
        }
    }
    /**
     * Decrease the value of the prompt by the step.
     */
    protected function decrease_value(): void
    {
        if ($this->typed_value === '') {
            $this->typed_value = (string) ($this->max === PHP_INT_MAX ? 0 : $this->max);
            $this->cursor_position++;
            return;
        }
        if (is_numeric($this->typed_value)) {
            $previous_value_length = mb_strlen($this->typed_value);
            $this->typed_value = (string) max($this->min, (int) $this->typed_value - $this->step);
            if (mb_strlen($this->typed_value) < $previous_value_length) {
                $this->cursor_position--;
            }
        }
    }
    public function value(): int|string
    {
        if (is_numeric($this->typed_value)) {
            return (int) $this->typed_value;
        }
        return $this->typed_value;
    }
    /**
     * Get the entered value with a virtual cursor.
     */
    public function value_with_cursor(int $max_width): string
    {
        if ($this->value() === '') {
            return $this->dim($this->add_cursor($this->placeholder, 0, $max_width));
        }
        return $this->add_cursor((string) $this->value(), $this->cursor_position, $max_width);
    }
}