<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

trait Erase
{
    /**
     * Erase the given number of lines downwards from the cursor position.
     */
    public function erase_lines(int $count): void
    {
        $clear = '';
        for ($i = 0; $i < $count; $i++) {
            $clear .= "\x1b[2K" . ($i < $count - 1 ? "\x1b[{$count}A" : '');
        }
        if ($count) {
            $clear .= "\x1b[G";
        }
        static::write_directly($clear);
    }
    /**
     * Erase from cursor until end of screen.
     */
    public function erase_down(): void
    {
        static::write_directly("\x1b[J");
    }
}