<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Confirm_Prompt;
class Confirm_Prompt_Renderer extends Renderer
{
    use Concerns\Draws_Boxes;
    /**
     * Render the confirm prompt.
     */
    public function __invoke(Confirm_Prompt $prompt): string
    {
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->truncate($prompt->label(), $prompt->terminal()->cols() - 6)),
            'cancel' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->render_options($prompt), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->render_options($prompt), color: 'yellow')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->render_options($prompt))->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
    /**
     * Render the confirm prompt options.
     */
    protected function render_options(Confirm_Prompt $prompt): string
    {
        $length = (int) floor(($prompt->terminal()->cols() - 14) / 2);
        $yes = $this->truncate($prompt->yes, $length);
        $no = $this->truncate($prompt->no, $length);
        if ($prompt->state === 'cancel') {
            return $this->dim($prompt->confirmed ? "● {$this->strikethrough($yes)} / ○ {$this->strikethrough($no)}" : "○ {$this->strikethrough($yes)} / ● {$this->strikethrough($no)}");
        }
        return $prompt->confirmed ? "{$this->green('●')} {$yes} {$this->dim('/ ○ ' . $no)}" : "{$this->dim('○ ' . $yes . ' /')} {$this->green('●')} {$no}";
    }
}