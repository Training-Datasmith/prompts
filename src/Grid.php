<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Illuminate\Support\Collection;
class Grid extends Prompt
{
    /**
     * The grid items.
     *
     * @var array<int, string>
     */
    public array $items;
    /**
     * The maximum width of the grid.
     */
    public int $max_width;
    /**
     * Create a new Grid instance.
     *
     * @param  array<int, string>|Collection<int, string>  $items
     */
    public function __construct(array|Collection $items = [], ?int $max_width = null)
    {
        $this->items = $items instanceof Collection ? $items->all() : $items;
        $this->max_width = $max_width ?? static::terminal()->cols() ?: 80;
    }
    /**
     * Display the grid.
     */
    public function display(): void
    {
        $this->prompt();
    }
    /**
     * Display the grid.
     */
    public function prompt(): bool
    {
        if ($this->items === []) {
            return true;
        }
        $this->capture_previous_new_lines();
        $this->state = 'submit';
        static::output()->write($this->render_theme());
        return true;
    }
    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}