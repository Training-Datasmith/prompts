<?php

declare (strict_types=1);
namespace Laravel\Prompts;

class Title extends Prompt
{
    public function __construct(public string $title)
    {
    }
    /**
     * Update the title of the terminal.
     */
    public function prompt(): bool
    {
        $this->write_directly($this->render_theme());
        return true;
    }
    /**
     * Update the title of the terminal.
     */
    public function display(): void
    {
        $this->prompt();
    }
    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}