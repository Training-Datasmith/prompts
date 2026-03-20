<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Suggest_Prompt;
use Laravel\Prompts\Themes\Contracts\Scrolling;
class Suggest_Prompt_Renderer extends Renderer implements Scrolling
{
    use Concerns\Draws_Boxes;
    use Concerns\Draws_Scrollbars;
    /**
     * Render the suggest prompt.
     */
    public function __invoke(Suggest_Prompt $prompt): string
    {
        $max_width = $prompt->terminal()->cols() - 6;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->truncate($prompt->value(), $max_width)),
            'cancel' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->strikethrough($this->dim($this->truncate($prompt->value() ?: $prompt->placeholder, $max_width))), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->value_with_cursor_and_arrow($prompt, $max_width), $this->render_options($prompt), color: 'yellow')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->value_with_cursor_and_arrow($prompt, $max_width), $this->render_options($prompt), info: $prompt->info_text())->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line())->space_for_dropdown($prompt),
        };
    }
    /**
     * Render the value with the cursor and an arrow.
     */
    protected function value_with_cursor_and_arrow(Suggest_Prompt $prompt, int $max_width): string
    {
        if ($prompt->highlighted !== null || $prompt->value() !== '' || count($prompt->matches()) === 0) {
            return $prompt->value_with_cursor($max_width);
        }
        return preg_replace('/\s$/', $this->cyan('⌄'), $this->pad($prompt->value_with_cursor($max_width - 1) . '  ', min($this->longest($prompt->matches(), padding: 2), $max_width)));
    }
    /**
     * Render a spacer to prevent jumping when the suggestions are displayed.
     */
    protected function space_for_dropdown(Suggest_Prompt $prompt): self
    {
        if ($prompt->value() === '' && $prompt->highlighted === null) {
            $this->new_line(min(count($prompt->matches()), $prompt->scroll, $prompt->terminal()->lines() - 7) + 1);
        }
        return $this;
    }
    /**
     * Render the options.
     */
    protected function render_options(Suggest_Prompt $prompt): string
    {
        if (empty($prompt->matches()) || $prompt->value() === '' && $prompt->highlighted === null) {
            return '';
        }
        return implode(PHP_EOL, $this->scrollbar(array_map(function (int|string $label, int|string $key) use ($prompt): string {
            $label = $this->truncate($label, $prompt->terminal()->cols() - 12);
            return $prompt->highlighted === $key ? "{$this->cyan('›')} {$label}  " : "  {$this->dim($label)}  ";
        }, $visible = $prompt->visible(), array_keys($visible)), $prompt->first_visible, $prompt->scroll, count($prompt->matches()), min($this->longest($prompt->matches(), padding: 4), $prompt->terminal()->cols() - 6), $prompt->state === 'cancel' ? 'dim' : 'cyan'));
    }
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int
    {
        return 7;
    }
}