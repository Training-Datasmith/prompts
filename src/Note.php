<?php

declare (strict_types=1);
namespace Laravel\Prompts;

class Note extends Prompt
{
    /**
     * Create a new Note instance.
     */
    public function __construct(public string $message, public ?string $type = null)
    {
    }
    /**
     * Display the note.
     */
    public function display(): void
    {
        $this->prompt();
    }
    /**
     * Display the note.
     */
    public function prompt(): bool
    {
        $this->capture_previous_new_lines();
        if (static::should_fallback()) {
            return $this->fallback();
        }
        $this->state = 'submit';
        static::output()->write($this->render_theme());
        return true;
    }
    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}