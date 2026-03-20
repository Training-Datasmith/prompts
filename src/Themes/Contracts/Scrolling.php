<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Contracts;

interface Scrolling
{
    /**
     * The number of lines to reserve outside of the scrollable area.
     */
    public function reserved_lines(): int;
}