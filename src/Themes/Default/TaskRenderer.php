<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default;

use Laravel\Prompts\Concerns\Has_Spinner;
use Laravel\Prompts\Task;
class Task_Renderer extends Renderer
{
    use Has_Spinner;
    /**
     * Render the task.
     */
    public function __invoke(Task $task): string
    {
        if ($task->static) {
            return $this->line(" {$this->cyan($this->static_frame)} {$task->label}");
        }
        $task->interval = $this->interval;
        $this->line(" {$this->cyan($this->spinner_frame($task->count))} {$task->label}");
        $lead_padding = str_repeat(' ', 3);
        $stable_messages = array_slice($task->stable_messages, -$task->max_stable_messages);
        foreach ($stable_messages as $stable_message) {
            $symbol = match ($stable_message['type']) {
                'success' => $this->green('✔'),
                'error' => $this->red('✘'),
                'warning' => $this->yellow('⚠'),
                default => '',
            };
            $this->line($lead_padding . $symbol . ' ' . $stable_message['message']);
        }
        if (count($task->stable_messages) > 0 || count($task->logs) > 0) {
            $this->line($this->gray(' ' . str_repeat('─', $this->prompt->terminal()->cols() - 10)));
        } else {
            $this->new_line();
        }
        $logs = array_slice($task->logs, -$task->limit);
        foreach ($logs as $log) {
            $this->line(' ' . $this->dim($log));
        }
        $remaining = $task->limit - count($task->logs);
        while ($remaining > 0) {
            $this->line('');
            $remaining--;
        }
        return $this;
    }
}