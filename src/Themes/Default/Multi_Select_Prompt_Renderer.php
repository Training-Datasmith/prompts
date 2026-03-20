<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Multi_Select_Prompt;
use Laravel\Prompts\Themes\Contracts\Scrolling;
class Multi_Select_Prompt_Renderer extends Renderer implements Scrolling
{
    use Concerns\Draws_Boxes;
    use Concerns\Draws_Scrollbars;
    /**
     * Render the multiselect prompt.
     */
    public function __invoke(Multi_Select_Prompt $prompt): string
    {
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->render_selected_options($prompt)),
            'cancel' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->render_options($prompt), color: 'red')->error($prompt->cancel_message),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->render_options($prompt), color: 'yellow', info: count($prompt->options) > $prompt->scroll ? count($prompt->value()) . ' selected' : '')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->render_options($prompt), info: $this->get_info_text($prompt))->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
    /**
     * Render the options.
     */
    protected function render_options(Multi_Select_Prompt $prompt): string
    {
        return implode(PHP_EOL, $this->scrollbar(array_values(array_map(function (int|string $label, int|string $key) use ($prompt): string {
            $label = $this->truncate($label, $prompt->terminal()->cols() - 12);
            $index = array_search($key, array_keys($prompt->options));
            $active = $index === $prompt->highlighted;
            if (array_is_list($prompt->options)) {
                $value = $prompt->options[$index];
            } else {
                $value = array_keys($prompt->options)[$index];
            }
            $selected = in_array($value, $prompt->value());
            if ($prompt->state === 'cancel') {
                return $this->dim(match (true) {
                    $active && $selected => "› ◼ {$this->strikethrough($label)}  ",
                    $active => "› ◻ {$this->strikethrough($label)}  ",
                    $selected => "  ◼ {$this->strikethrough($label)}  ",
                    default => "  ◻ {$this->strikethrough($label)}  ",
                });
            }
            return match (true) {
                $active && $selected => "{$this->cyan('› ◼')} {$label}  ",
                $active => "{$this->cyan('›')} ◻ {$label}  ",
                $selected => "  {$this->cyan('◼')} {$this->dim($label)}  ",
                default => "  {$this->dim('◻')} {$this->dim($label)}  ",
            };
        }, $visible = $prompt->visible(), array_keys($visible))), $prompt->first_visible, $prompt->scroll, count($prompt->options), min($this->longest($prompt->options, padding: 6), $prompt->terminal()->cols() - 6), $prompt->state === 'cancel' ? 'dim' : 'cyan'));
    }
    /**
     * Render the selected options.
     */
    protected function render_selected_options(Multi_Select_Prompt $prompt): string
    {
        if (count($prompt->labels()) === 0) {
            return $this->gray('None');
        }
        return implode("\n", array_map(fn(string $label): string => $this->truncate($label, $prompt->terminal()->cols() - 6), $prompt->labels()));
    }
    /**
     * Render the info text.
     */
    protected function get_info_text(Multi_Select_Prompt $prompt): string
    {
        $parts = array_filter([$prompt->info_text(), count($prompt->options) > $prompt->scroll ? count($prompt->value()) . ' selected' : '']);
        return implode(' · ', $parts);
    }
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int
    {
        return 5;
    }
}