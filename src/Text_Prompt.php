<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
class Text_Prompt extends Prompt
{
    use Concerns\Typed_Value;
    /**
     * Create a new TextPrompt instance.
     */
    public function __construct(public string $label, public string $placeholder = '', public string $default = '', public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null)
    {
        $this->track_typed_value($default);
    }
    /**
     * Get the entered value with a virtual cursor.
     */
    public function value_with_cursor(int $max_width): string
    {
        if ($this->value() === '') {
            return $this->dim($this->add_cursor($this->placeholder, 0, $max_width));
        }
        return $this->add_cursor($this->value(), $this->cursor_position, $max_width);
    }
}