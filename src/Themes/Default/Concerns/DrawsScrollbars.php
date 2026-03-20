<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default\Concerns;

use Illuminate\Support\Collection;
trait Draws_Scrollbars
{
    /**
     * Render a scrollbar beside the visible items.
     *
     * @template T of array<int, string>|\Illuminate\Support\Collection<int, string>
     *
     * @param  T  $visible
     * @return T
     */
    protected function scrollbar(array|Collection $visible, int $first_visible, int $height, int $total, int $width, string $color = 'cyan'): array|Collection
    {
        if ($height >= $total) {
            return $visible;
        }
        $scroll_position = $this->scroll_position($first_visible, $height, $total);
        $lines = $visible instanceof Collection ? $visible->all() : $visible;
        $result = array_map(fn($line, $index): string|array => match ($index) {
            $scroll_position => preg_replace('/.$/', $this->{$color}('┃'), $this->pad($line, $width)) ?? '',
            default => preg_replace('/.$/', $this->gray('│'), $this->pad($line, $width)) ?? '',
        }, array_values($lines), range(0, count($lines) - 1));
        return $visible instanceof Collection ? new Collection($result) : $result;
        // @phpstan-ignore return.type (https://github.com/phpstan/phpstan/issues/11663)
    }
    /**
     * Return the position where the scrollbar "handle" should be rendered.
     */
    protected function scroll_position(int $first_visible, int $height, int $total): int
    {
        if ($first_visible === 0) {
            return 0;
        }
        $max_position = $total - $height;
        if ($first_visible === $max_position) {
            return $height - 1;
        }
        if ($height <= 2) {
            return -1;
        }
        $percent = $first_visible / $max_position;
        return (int) round($percent * ($height - 3)) + 1;
    }
}