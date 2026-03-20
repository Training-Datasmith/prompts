<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Textarea_Prompt;
use Laravel\Prompts\Themes\Contracts\Scrolling;
class Textarea_Prompt_Renderer extends Renderer implements Scrolling
{
    use Concerns\Draws_Boxes;
    use Concerns\Draws_Scrollbars;
    /**
     * Render the textarea prompt.
     */
    public function __invoke(Textarea_Prompt $prompt): string
    {
        $prompt->width = $prompt->terminal()->cols() - 8;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->width)), implode(PHP_EOL, $prompt->lines())),
            'cancel' => $this->box($this->truncate($prompt->label, $prompt->width), implode(PHP_EOL, array_map(fn(string $line): string => $this->strikethrough($this->dim($line)), $prompt->lines())), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->width), $this->render_text($prompt), color: 'yellow', info: 'Ctrl+D to submit')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->width)), $this->render_text($prompt), info: 'Ctrl+D to submit')->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
    /**
     * Render the text in the prompt.
     */
    protected function render_text(Textarea_Prompt $prompt): string
    {
        $visible = $prompt->visible();
        while (count($visible) < $prompt->scroll) {
            $visible[] = '';
        }
        $longest = $this->longest($prompt->lines()) + 2;
        return implode(PHP_EOL, $this->scrollbar($visible, $prompt->first_visible, $prompt->scroll, count($prompt->lines()), min($longest, $prompt->width + 2)));
    }
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int
    {
        return 5;
    }
}