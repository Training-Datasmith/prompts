<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

trait Cursor
{
    /**
     * Indicates if the cursor has been hidden.
     */
    protected static bool $cursor_hidden = false;
    /**
     * Hide the cursor.
     */
    public function hide_cursor(): void
    {
        static::write_directly("\x1b[?25l");
        static::$cursor_hidden = true;
    }
    /**
     * Show the cursor.
     */
    public function show_cursor(): void
    {
        static::write_directly("\x1b[?25h");
        static::$cursor_hidden = false;
    }
    /**
     * Restore the cursor if it was hidden.
     */
    public function restore_cursor(): void
    {
        if (static::$cursor_hidden) {
            $this->show_cursor();
        }
    }
    /**
     * Move the cursor.
     */
    public function move_cursor(int $x, int $y = 0): void
    {
        $sequence = '';
        if ($x < 0) {
            $sequence .= "\x1b[" . abs($x) . 'D';
            // Left
        } elseif ($x > 0) {
            $sequence .= "\x1b[{$x}C";
            // Right
        }
        if ($y < 0) {
            $sequence .= "\x1b[" . abs($y) . 'A';
            // Up
        } elseif ($y > 0) {
            $sequence .= "\x1b[{$y}B";
            // Down
        }
        static::write_directly($sequence);
    }
    /**
     * Move the cursor to the given column.
     */
    public function move_cursor_to_column(int $column): void
    {
        static::write_directly("\x1b[{$column}G");
    }
    /**
     * Move the cursor up by the given number of lines.
     */
    public function move_cursor_up(int $lines): void
    {
        static::write_directly("\x1b[{$lines}A");
    }
}