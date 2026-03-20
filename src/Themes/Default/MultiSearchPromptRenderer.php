<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Multi_Search_Prompt;
use Laravel\Prompts\Themes\Contracts\Scrolling;
class Multi_Search_Prompt_Renderer extends Renderer implements Scrolling
{
    use Concerns\Draws_Boxes;
    use Concerns\Draws_Scrollbars;
    /**
     * Render the suggest prompt.
     */
    public function __invoke(Multi_Search_Prompt $prompt): string
    {
        $max_width = $prompt->terminal()->cols() - 6;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->render_selected_options($prompt)),
            'cancel' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->strikethrough($this->dim($this->truncate($prompt->search_value() ?: $prompt->placeholder, $max_width))), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $prompt->value_with_cursor($max_width), $this->render_options($prompt), color: 'yellow', info: $this->get_info_text($prompt))->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            'searching' => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->value_with_cursor_and_search_icon($prompt, $max_width), $this->render_options($prompt), info: $this->get_info_text($prompt))->hint($prompt->hint),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $prompt->value_with_cursor($max_width), $this->render_options($prompt), info: $this->get_info_text($prompt))->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line())->space_for_dropdown($prompt),
        };
    }
    /**
     * Render the value with the cursor and a search icon.
     */
    protected function value_with_cursor_and_search_icon(Multi_Search_Prompt $prompt, int $max_width): string
    {
        return preg_replace('/\s$/', $this->cyan('…'), $this->pad($prompt->value_with_cursor($max_width - 1) . '  ', min($this->longest($prompt->matches(), padding: 2), $max_width)));
    }
    /**
     * Render a spacer to prevent jumping when the suggestions are displayed.
     */
    protected function space_for_dropdown(Multi_Search_Prompt $prompt): self
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
    protected function render_options(Multi_Search_Prompt $prompt): string
    {
        if ($prompt->search_value() !== '' && empty($prompt->matches())) {
            return $this->gray('  ' . ($prompt->state === 'searching' ? 'Searching...' : 'No results.'));
        }
        return implode(PHP_EOL, $this->scrollbar(array_map(function (int|string $label, int|string $key) use ($prompt): string {
            $label = $this->truncate($label, $prompt->terminal()->cols() - 12);
            $index = array_search($key, array_keys($prompt->matches()));
            $active = $index === $prompt->highlighted;
            $selected = $prompt->is_list() ? in_array($label, $prompt->value()) : in_array($key, $prompt->value());
            return match (true) {
                $active && $selected => "{$this->cyan('› ◼')} {$label}  ",
                $active => "{$this->cyan('›')} ◻ {$label}  ",
                $selected => "  {$this->cyan('◼')} {$this->dim($label)}  ",
                default => "  {$this->dim('◻')} {$this->dim($label)}  ",
            };
        }, $prompt->visible(), array_keys($prompt->visible())), $prompt->first_visible, $prompt->scroll, count($prompt->matches()), min($this->longest($prompt->matches(), padding: 4), $prompt->terminal()->cols() - 6)));
    }
    /**
     * Render the selected options.
     */
    protected function render_selected_options(Multi_Search_Prompt $prompt): string
    {
        if (count($prompt->labels()) === 0) {
            return $this->gray('None');
        }
        return implode("\n", array_map(fn(string $label): string => $this->truncate($label, $prompt->terminal()->cols() - 6), $prompt->labels()));
    }
    /**
     * Render the info text.
     */
    protected function get_info_text(Multi_Search_Prompt $prompt): string
    {
        $selected = count($prompt->value()) . ' selected';
        $hidden_count = count($prompt->value()) - count(array_filter($prompt->matches(), fn($label, $key): bool => in_array($prompt->is_list() ? $label : $key, $prompt->value()), ARRAY_FILTER_USE_BOTH));
        if ($hidden_count > 0) {
            $selected .= " ({$hidden_count} hidden)";
        }
        $parts = array_filter([$prompt->info_text(), $selected]);
        return implode(' · ', $parts);
    }
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int
    {
        return 7;
    }
}