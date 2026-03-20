<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

class Clear_Renderer extends Renderer
{
    /**
     * Clear the terminal.
     */
    public function __invoke(): string
    {
        return "\x1b[H\x1b[J";
    }
}