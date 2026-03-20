<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

trait Has_Spinner
{
    /**
     * The frames of the spinner (single dot moving around the perimeter).
     *
     * @var array<string>
     */
    protected array $frames = ['⠂', '⠒', '⠐', '⠰', '⠠', '⠤', '⠄', '⠆'];
    /**
     * The frame to render when the spinner is static.
     */
    protected string $static_frame = '⠶';
    /**
     * The interval between frames.
     */
    protected int $interval = 75;
    public function spinner_frame(int $count): string
    {
        return $this->frames[$count % count($this->frames)];
    }
}