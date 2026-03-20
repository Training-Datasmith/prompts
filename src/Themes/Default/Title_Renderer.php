<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Title;
class Title_Renderer extends Renderer
{
    /**
     * Render the title.
     */
    public function __invoke(Title $title): string
    {
        return "\x1b]0;{$title->title}\x07";
    }
}