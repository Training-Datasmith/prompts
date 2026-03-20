<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

use Laravel\Prompts\Output\Buffered_Console_Output;
use function Termwind\render;
use function Termwind\Render_Using;
trait Termwind
{
    protected function termwind(string $html)
    {
        render_using($output = new Buffered_Console_Output());
        render($html);
        return $this->restore_escape_sequences($output->fetch());
    }
    protected function restore_escape_sequences(string $string): ?string
    {
        return preg_replace('/\[(\d+)m/', "\x1b[" . '\1m', $string);
    }
}