<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Output\Buffered_Console_Output;
use Laravel\Prompts\Table;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\Table_Style;
class Table_Renderer extends Renderer
{
    /**
     * Render the table.
     */
    public function __invoke(Table $table): string
    {
        $table_style = (new Table_Style())->set_horizontal_border_chars('─')->set_vertical_border_chars('│', '│')->set_cell_header_format($this->dim('<fg=default>%s</>'))->set_cell_row_format('<fg=default>%s</>');
        if (empty($table->headers)) {
            $table_style->set_crossing_chars('┼', '', '', '', '┤', '┘</>', '┴', '└', '├', '<fg=gray>┌', '┬', '┐');
        } else {
            $table_style->set_crossing_chars('┼', '<fg=gray>┌', '┬', '┐', '┤', '┘</>', '┴', '└', '├');
        }
        $buffered = new Buffered_Console_Output();
        (new Symfony_Table($buffered))->set_headers($table->headers)->set_rows($table->rows)->set_style($table_style)->render();
        foreach (explode(PHP_EOL, trim($buffered->content(), PHP_EOL)) as $line) {
            $this->line(' ' . $line);
        }
        return $this;
    }
}