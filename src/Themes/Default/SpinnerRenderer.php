<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Concerns\Has_Spinner;
use Laravel\Prompts\Spinner;
class Spinner_Renderer extends Renderer
{
    use Has_Spinner;
    /**
     * Render the spinner.
     */
    public function __invoke(Spinner $spinner): string
    {
        if ($spinner->static) {
            return $this->line(" {$this->cyan($this->static_frame)} {$spinner->message}");
        }
        $spinner->interval = $this->interval;
        return $this->line(" {$this->cyan($this->spinner_frame($spinner->count))} {$spinner->message}");
    }
}