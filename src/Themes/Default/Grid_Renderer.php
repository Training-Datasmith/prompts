<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Grid;
use Laravel\Prompts\Output\Buffered_Console_Output;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\Table_Separator;
use Symfony\Component\Console\Helper\Table_Style;
class Grid_Renderer extends Renderer
{
    use Concerns\Interacts_With_Strings;
    protected int $min_width = 60;
    /**
     * Render the grid.
     */
    public function __invoke(Grid $grid): string
    {
        if (empty($grid->items)) {
            return $this;
        }
        $max_width = $grid->max_width - 2;
        $cell_width = max(array_map(fn(string $item): int => mb_strwidth($this->strip_escape_sequences($item)), $grid->items)) + 4;
        $max_columns = max(1, (int) floor(($max_width - 1) / ($cell_width + 1)));
        $column_count = max(1, $this->balanced_column_count(count($grid->items), $max_columns));
        $rows = $this->build_rows_with_separators($grid->items, $column_count);
        $table_style = (new Table_Style())->set_horizontal_border_chars('─')->set_vertical_border_chars('│', '│')->set_cell_row_format('<fg=default>%s</>')->set_crossing_chars('┼', '', '', '', '┤', '┘', '┴', '└', '├', '┌', '┬', '┐');
        $buffered = new Buffered_Console_Output();
        (new Symfony_Table($buffered))->set_rows($rows)->set_style($table_style)->render();
        foreach (explode(PHP_EOL, trim($buffered->content(), PHP_EOL)) as $line) {
            $this->line(' ' . $line);
        }
        return $this;
    }
    /**
     * Calculate a balanced column count for even row distribution.
     */
    protected function balanced_column_count(int $item_count, int $max_columns): int
    {
        if ($item_count <= $max_columns) {
            return $item_count;
        }
        for ($cols = $max_columns; $cols >= 1; $cols--) {
            $remainder = $item_count % $cols;
            if ($remainder === 0 || $remainder >= (int) ceil($cols / 2)) {
                return $cols;
            }
        }
        return $max_columns;
    }
    /**
     * Build rows with separators between them.
     *
     * @param  array<int, string>  $items
     * @param  int<1, max>  $columnCount
     * @return array<int, array<int, string>|TableSeparator>
     */
    protected function build_rows_with_separators(array $items, int $column_count): array
    {
        $chunks = array_chunk($items, $column_count);
        $rows = [];
        foreach ($chunks as $index => $chunk) {
            if ($index > 0) {
                $rows[] = new Table_Separator();
            }
            $rows[] = array_pad($chunk, $column_count, '');
        }
        return $rows;
    }
}