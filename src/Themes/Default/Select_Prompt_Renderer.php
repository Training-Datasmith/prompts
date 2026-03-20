<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Select_Prompt;
use Laravel\Prompts\Themes\Contracts\Scrolling;
class Select_Prompt_Renderer extends Renderer implements Scrolling
{
    use Concerns\Draws_Boxes;
    use Concerns\Draws_Scrollbars;
    /**
     * Render the select prompt.
     */
    public function __invoke(Select_Prompt $prompt): string
    {
        $max_width = $prompt->terminal()->cols() - 6;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->truncate($prompt->label(), $max_width)),
            'cancel' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->render_options($prompt), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->render_options($prompt), color: 'yellow')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->render_options($prompt), info: $prompt->info_text())->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
    /**
     * Render the options.
     */
    protected function render_options(Select_Prompt $prompt): string
    {
        return implode(PHP_EOL, $this->scrollbar(array_values(array_map(function (int|string $label, int|string $key) use ($prompt): string {
            $label = $this->truncate($label, $prompt->terminal()->cols() - 12);
            $index = array_search($key, array_keys($prompt->options));
            if ($prompt->state === 'cancel') {
                return $this->dim($prompt->highlighted === $index ? "› ● {$this->strikethrough($label)}  " : "  ○ {$this->strikethrough($label)}  ");
            }
            return $prompt->highlighted === $index ? "{$this->cyan('›')} {$this->cyan('●')} {$label}  " : "  {$this->dim('○')} {$this->dim($label)}  ";
        }, $visible = $prompt->visible(), array_keys($visible))), $prompt->first_visible, $prompt->scroll, count($prompt->options), min($this->longest($prompt->options, padding: 6), $prompt->terminal()->cols() - 6), $prompt->state === 'cancel' ? 'dim' : 'cyan'));
    }
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int
    {
        return 5;
    }
}