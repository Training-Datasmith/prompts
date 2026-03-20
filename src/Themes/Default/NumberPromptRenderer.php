<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Number_Prompt;
class Number_Prompt_Renderer extends Renderer
{
    use Concerns\Draws_Boxes;
    protected string $up_arrow = '▲';
    protected string $down_arrow = '▼';
    /**
     * Render the number prompt.
     */
    public function __invoke(Number_Prompt $prompt): string
    {
        $max_width = $prompt->terminal()->cols() - 6;
        return match ($prompt->state) {
            'submit' => $this->box($this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->truncate((string) $prompt->value(), $max_width)),
            'cancel' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->strikethrough($this->dim($this->truncate((string) $prompt->value() ?: $prompt->placeholder, $max_width))), color: 'red')->error('Cancelled.'),
            'error' => $this->box($this->truncate($prompt->label, $prompt->terminal()->cols() - 6), $this->with_arrows($prompt, $prompt->value_with_cursor($max_width), 'yellow'), color: 'yellow')->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),
            default => $this->box($this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)), $this->with_arrows($prompt, $prompt->value_with_cursor($max_width)))->when($prompt->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($prompt->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
    protected function with_arrows(Number_Prompt $prompt, int|string $value, ?string $color = null): string
    {
        $arrows = $this->get_arrows($prompt, $color);
        $value_length = mb_strwidth($this->strip_escape_sequences((string) $value));
        $padding = $this->min_width - $value_length - mb_strwidth($this->strip_escape_sequences($arrows));
        return $value . str_repeat(' ', $padding) . $arrows;
    }
    protected function get_arrows(Number_Prompt $prompt, ?string $color = null): string
    {
        $up_arrow = $this->up_arrow;
        $down_arrow = $this->down_arrow;
        if ($color) {
            $up_arrow = $this->{$color}($up_arrow);
            $down_arrow = $this->{$color}($down_arrow);
        }
        if (is_numeric($prompt->value())) {
            if ((int) $prompt->value() === $prompt->min) {
                $down_arrow = $this->dim($down_arrow);
            }
            if ((int) $prompt->value() === $prompt->max) {
                $up_arrow = $this->dim($up_arrow);
            }
            return $up_arrow . $down_arrow;
        }
        if ($prompt->value() === '') {
            return $up_arrow . $down_arrow;
        }
        return $this->dim($up_arrow) . $this->dim($down_arrow);
    }
}