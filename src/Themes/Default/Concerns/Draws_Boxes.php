<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default\Concerns;

use Laravel\Prompts\Prompt;
trait Draws_Boxes
{
    use Interacts_With_Strings;
    protected int $min_width = 60;
    /**
     * Draw a box.
     *
     * @return $this
     */
    protected function box(string $title, string $body, string $footer = '', string $color = 'gray', string $info = ''): self
    {
        $this->min_width = min($this->min_width, Prompt::terminal()->cols() - 6);
        $body_lines = explode(PHP_EOL, $body);
        $footer_lines = array_filter(explode(PHP_EOL, $footer));
        $width = $this->longest(array_merge($body_lines, $footer_lines, [$title]));
        $title_length = mb_strwidth($this->strip_escape_sequences($title));
        $title_label = $title_length > 0 ? " {$title} " : '';
        $top_border = str_repeat('─', $width - $title_length + ($title_length > 0 ? 0 : 2));
        $this->line("{$this->{$color}(' ┌')}{$title_label}{$this->{$color}($top_border . '┐')}");
        foreach ($body_lines as $line) {
            $this->line("{$this->{$color}(' │')} {$this->pad($line, $width)} {$this->{$color}('│')}");
        }
        if (count($footer_lines) > 0) {
            $this->line($this->{$color}(' ├' . str_repeat('─', $width + 2) . '┤'));
            foreach ($footer_lines as $line) {
                $this->line("{$this->{$color}(' │')} {$this->pad($line, $width)} {$this->{$color}('│')}");
            }
        }
        if ($info) {
            $info = $this->truncate($info, $width - 1);
        }
        $this->line($this->{$color}(' └' . str_repeat('─', $info ? $width - mb_strwidth($this->strip_escape_sequences($info)) : $width + 2) . ($info ? " {$info} " : '') . '┘'));
        return $this;
    }
}