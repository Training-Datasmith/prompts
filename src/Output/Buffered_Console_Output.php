<?php

declare (strict_types=1);
namespace Laravel\Prompts\Output;

class Buffered_Console_Output extends Console_Output
{
    /**
     * The output buffer.
     */
    protected string $buffer = '';
    /**
     * Empties the buffer and returns its content.
     */
    public function fetch(): string
    {
        $content = $this->buffer;
        $this->buffer = '';
        return $content;
    }
    /**
     * Return the content of the buffer.
     */
    public function content(): string
    {
        return $this->buffer;
    }
    /**
     * Write to the output buffer.
     */
    protected function do_write(string $message, bool $newline): void
    {
        $this->buffer .= $message;
        if ($newline) {
            $this->buffer .= \PHP_EOL;
        }
    }
    /**
     * Write output directly, bypassing newline capture.
     */
    public function write_directly(string $message): void
    {
        $this->do_write($message, false);
    }
}