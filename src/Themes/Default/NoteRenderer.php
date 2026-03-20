<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Note;
class Note_Renderer extends Renderer
{
    /**
     * Render the note.
     */
    public function __invoke(Note $note): string
    {
        $lines = explode(PHP_EOL, $note->message);
        switch ($note->type) {
            case 'intro':
            case 'outro':
                $lines = array_map(fn($line): string => " {$line} ", $lines);
                $longest = max(array_map(mb_strlen(...), $lines));
                foreach ($lines as $line) {
                    $line = mb_str_pad($line, $longest, ' ');
                    $this->line(" {$this->bg_cyan($this->black($line))}");
                }
                return $this;
            case 'warning':
                foreach ($lines as $line) {
                    $this->line($this->yellow(" {$line}"));
                }
                return $this;
            case 'error':
                foreach ($lines as $line) {
                    $this->line($this->red(" {$line}"));
                }
                return $this;
            case 'alert':
                foreach ($lines as $line) {
                    $this->line(" {$this->bg_red($this->white(" {$line} "))}");
                }
                return $this;
            case 'info':
                foreach ($lines as $line) {
                    $this->line($this->green(" {$line}"));
                }
                return $this;
            default:
                foreach ($lines as $line) {
                    $this->line(" {$line}");
                }
                return $this;
        }
    }
}