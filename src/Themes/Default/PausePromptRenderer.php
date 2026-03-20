<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Pause_Prompt;
class Pause_Prompt_Renderer extends Renderer
{
    use Concerns\Draws_Boxes;
    /**
     * Render the pause prompt.
     */
    public function __invoke(Pause_Prompt $prompt): string
    {
        $lines = explode(PHP_EOL, $prompt->message);
        $color = $prompt->state === 'submit' ? 'green' : 'gray';
        foreach ($lines as $line) {
            $this->line(" {$this->{$color}($line)}");
        }
        return $this;
    }
}