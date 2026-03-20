<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

use Laravel\Prompts\Themes\Contracts\Scrolling as ScrollingRenderer;
trait Scrolling
{
    /**
     * The number of items to display before scrolling.
     */
    public int $scroll;
    /**
     * The index of the highlighted option.
     */
    public ?int $highlighted;
    /**
     * The index of the first visible option.
     */
    public int $first_visible = 0;
    /**
     * Initialize scrolling.
     */
    protected function initialize_scrolling(?int $highlighted = null): void
    {
        $this->highlighted = $highlighted;
        $this->reduce_scrolling_to_fit_terminal();
    }
    /**
     * Reduce the scroll property to fit the terminal height.
     */
    protected function reduce_scrolling_to_fit_terminal(): void
    {
        $reserved_lines = ($renderer = $this->get_renderer()) instanceof Scrolling_Renderer ? $renderer->reserved_lines() : 0;
        $this->scroll = max(1, min($this->scroll, $this->terminal()->lines() - $reserved_lines));
    }
    /**
     * Highlight the given index.
     */
    protected function highlight(?int $index): void
    {
        $this->highlighted = $index;
        if ($this->highlighted === null) {
            return;
        }
        if ($this->highlighted < $this->first_visible) {
            $this->first_visible = $this->highlighted;
        } elseif ($this->highlighted > $this->first_visible + $this->scroll - 1) {
            $this->first_visible = $this->highlighted - $this->scroll + 1;
        }
    }
    /**
     * Highlight the previous entry, or wrap around to the last entry.
     */
    protected function highlight_previous(int $total, bool $allow_null = false): void
    {
        if ($total === 0) {
            return;
        }
        if ($this->highlighted === null) {
            $this->highlight($total - 1);
        } elseif ($this->highlighted === 0) {
            $this->highlight($allow_null ? null : $total - 1);
        } else {
            $this->highlight($this->highlighted - 1);
        }
    }
    /**
     * Highlight the next entry, or wrap around to the first entry.
     */
    protected function highlight_next(int $total, bool $allow_null = false): void
    {
        if ($total === 0) {
            return;
        }
        if ($this->highlighted === $total - 1) {
            $this->highlight($allow_null ? null : 0);
        } else {
            $this->highlight(($this->highlighted ?? -1) + 1);
        }
    }
    /**
     * Center the highlighted option.
     */
    protected function scroll_to_highlighted(int $total): void
    {
        if ($this->highlighted < $this->scroll) {
            return;
        }
        $remaining = $total - $this->highlighted - 1;
        $half_scroll = (int) floor($this->scroll / 2);
        $end_offset = max(0, $half_scroll - $remaining);
        if ($this->scroll % 2 === 0) {
            $end_offset--;
        }
        $this->first_visible = $this->highlighted - $half_scroll - $end_offset;
    }
}