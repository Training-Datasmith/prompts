<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Password_Prompt;
class Password_Prompt_Renderer extends Renderer
{
    use Concerns\Draws_Boxes;
    /**
     * Render the password prompt.
     */
    public function __invoke(Password_Prompt $prompt): string
    {
        $max_width = $prompt->terminal()->cols() - 6;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($prompt->label), $this->truncate($prompt->masked(), $max_width)),
            'cancel' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->strikethrough($this->dim($this->truncate($prompt->masked() ?: $prompt->placeholder, $max_width))), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $prompt->masked_with_cursor($max_width), color: 'yellow')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $prompt->masked_with_cursor($max_width))->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
}