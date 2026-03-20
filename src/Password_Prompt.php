<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
class Password_Prompt extends Prompt
{
    use Concerns\Typed_Value;
    /**
     * Create a new PasswordPrompt instance.
     */
    public function __construct(public string $label, public string $placeholder = '', public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null)
    {
        $this->track_typed_value();
    }
    /**
     * Get a masked version of the entered value.
     */
    public function masked(): string
    {
        return str_repeat('•', mb_strlen($this->value()));
    }
    /**
     * Get the masked value with a virtual cursor.
     */
    public function masked_with_cursor(int $max_width): string
    {
        if ($this->value() === '') {
            return $this->dim($this->add_cursor($this->placeholder, 0, $max_width));
        }
        return $this->add_cursor($this->masked(), $this->cursor_position, $max_width);
    }
}