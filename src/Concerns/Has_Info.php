<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

use Closure;
trait Has_Info
{
    /**
     * Get the resolved info text.
     */
    public function info_text(): string
    {
        if ($this->info instanceof Closure) {
            return ($this->info)($this->highlighted_value()) ?? '';
        }
        return $this->info;
    }
}