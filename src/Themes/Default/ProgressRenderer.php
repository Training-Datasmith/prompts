<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Progress;
class Progress_Renderer extends Renderer
{
    use Concerns\Draws_Boxes;
    /**
     * The character to use for the progress bar.
     */
    protected string $bar_character = '█';
    /**
     * Render the progress bar.
     *
     * @param  Progress<int|iterable<mixed>>  $progress
     */
    public function __invoke(Progress $progress): string
    {
        $filled = str_repeat($this->bar_character, (int) ceil($progress->percentage() * min($this->min_width, $progress->terminal()->cols() - 6)));
        return match ($progress->state) {
            'submit' => $this->box($this->dim($this->truncate($progress->label, $progress->terminal()->cols() - 6)), $this->dim($filled), info: $this->fraction_completed($progress)),
            'error' => $this->box($this->truncate($progress->label, $progress->terminal()->cols() - 6), $this->dim($filled), color: 'red', info: $this->fraction_completed($progress)),
            'cancel' => $this->box($this->truncate($progress->label, $progress->terminal()->cols() - 6), $this->dim($filled), color: 'red', info: $this->fraction_completed($progress))->error($progress->cancel_message),
            default => $this->box($this->cyan($this->truncate($progress->label, $progress->terminal()->cols() - 6)), $this->dim($filled), info: $this->fraction_completed($progress))->when($progress->hint, fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->hint($progress->hint), fn(): \Laravel\Prompts\Themes\Default\Renderer => $this->new_line()),
        };
    }
    /**
     * @param  Progress<int|iterable<mixed>>  $progress
     */
    protected function fraction_completed(Progress $progress): string
    {
        return number_format($progress->progress) . ' / ' . number_format($progress->total);
    }
}