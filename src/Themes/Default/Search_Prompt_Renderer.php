<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Search_Prompt;
use Laravel\Prompts\Themes\Contracts\Scrolling;
class Search_Prompt_Renderer extends Renderer implements Scrolling
{
    use Concerns\Draws_Boxes;
    use Concerns\Draws_Scrollbars;
    /**
     * Render the suggest prompt.
     */
    public function __invoke(Search_Prompt $prompt): string
    {
        $max_width = $prompt->terminal()->cols() - 6;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->truncate($prompt->label(), $max_width)),
            'cancel' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->strikethrough($this->dim($this->truncate($prompt->search_value() ?: $prompt->placeholder, $max_width))), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $prompt->value_with_cursor($max_width), $this->render_options($prompt), color: 'yellow')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            'searching' => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->value_with_cursor_and_search_icon($prompt, $max_width), $this->render_options($prompt), info: $prompt->info_text())->hint($prompt->hint),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $prompt->value_with_cursor($max_width), $this->render_options($prompt), info: $prompt->info_text())->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line())->space_for_dropdown($prompt),
        };
    }
    /**
     * Render the value with the cursor and a search icon.
     */
    protected function value_with_cursor_and_search_icon(Search_Prompt $prompt, int $max_width): string
    {
        return preg_replace('/\s$/', $this->cyan('…'), $this->pad($prompt->value_with_cursor($max_width - 1) . '  ', min($this->longest($prompt->matches(), padding: 2), $max_width)));
    }
    /**
     * Render a spacer to prevent jumping when the suggestions are displayed.
     */
    protected function space_for_dropdown(Search_Prompt $prompt): self
    {
        if ($prompt->search_value() !== '') {
            return $this;
        }
        $this->new_line(max(0, min($prompt->scroll, $prompt->terminal()->lines() - 7) - count($prompt->matches())));
        if ($prompt->matches() === []) {
            $this->new_line();
        }
        return $this;
    }
    /**
     * Render the options.
     */
    protected function render_options(Search_Prompt $prompt): string
    {
        if ($prompt->search_value() !== '' && empty($prompt->matches())) {
            return $this->gray('  ' . ($prompt->state === 'searching' ? 'Searching...' : 'No results.'));
        }
        return implode(PHP_EOL, $this->scrollbar(array_values(array_map(function (int|string $label, int|string $key) use ($prompt): string {
            $label = $this->truncate($label, $prompt->terminal()->cols() - 10);
            $index = array_search($key, array_keys($prompt->matches()));
            return $prompt->highlighted === $index ? "{$this->cyan('›')} {$label}  " : "  {$this->dim($label)}  ";
        }, $visible = $prompt->visible(), array_keys($visible))), $prompt->first_visible, $prompt->scroll, count($prompt->matches()), min($this->longest($prompt->matches(), padding: 4), $prompt->terminal()->cols() - 6)));
    }
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int
    {
        return 7;
    }
}