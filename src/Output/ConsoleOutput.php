<?php

declare (strict_types=1);
namespace Laravel\Prompts\Output;

use Symfony\Component\Console\Output\Console_Output as SymfonyConsoleOutput;
class Console_Output extends Symfony_Console_Output
{
    /**
     * How many new lines were written by the last output.
     */
    protected int $new_lines_written = 1;
    /**
     * How many new lines were written by the last output.
     */
    public function new_lines_written(): int
    {
        return $this->new_lines_written;
    }
    /**
     * Write the output and capture the number of trailing new lines.
     */
    protected function do_write(string $message, bool $newline): void
    {
        parent::do_write($message, $newline);
        if ($newline) {
            $message .= \PHP_EOL;
        }
        $trailing_new_lines = strlen($message) - strlen(rtrim($message, \PHP_EOL));
        if (trim($message) === '') {
            $this->new_lines_written += $trailing_new_lines;
        } else {
            $this->new_lines_written = $trailing_new_lines;
        }
    }
    /**
     * Write output directly, bypassing newline capture.
     */
    public function write_directly(string $message): void
    {
        parent::do_write($message, false);
    }
}