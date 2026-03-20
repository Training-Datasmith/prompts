<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Symfony\Component\Process\Executable_Finder;
use Symfony\Component\Process\Process;
class Notify_Prompt extends Prompt
{
    /**
     * Create a new NotifyPrompt instance.
     */
    public function __construct(public string $title, public string $body = '', public string $subtitle = '', public string $sound = '', public string $icon = '')
    {
    }
    /**
     * Send the notification.
     */
    public function prompt(): bool
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->send_mac_os(),
            'Linux' => $this->send_linux(),
            default => false,
        };
    }
    /**
     * Send a notification on macOS using osascript.
     */
    protected function send_mac_os(): bool
    {
        $script = 'display notification ' . $this->escape_apple_script($this->body);
        $script .= ' with title ' . $this->escape_apple_script($this->title);
        if ($this->subtitle !== '') {
            $script .= ' subtitle ' . $this->escape_apple_script($this->subtitle);
        }
        if ($this->sound !== '') {
            $script .= ' sound name ' . $this->escape_apple_script($this->sound);
        }
        return $this->execute(['osascript', '-e', $script]);
    }
    /**
     * Send a notification on Linux, trying available notifiers.
     */
    protected function send_linux(): bool
    {
        $finder = new Executable_Finder();
        if ($finder->find('notify-send') !== null) {
            return $this->send_linux_notify_send();
        }
        if ($finder->find('kdialog') !== null) {
            return $this->send_linux_k_dialog();
        }
        return false;
    }
    /**
     * Send a notification using notify-send.
     */
    protected function send_linux_notify_send(): bool
    {
        $command = ['notify-send'];
        if ($this->icon !== '') {
            $command[] = '--icon';
            $command[] = $this->icon;
        }
        $command[] = $this->title;
        if ($this->body !== '') {
            $command[] = $this->body;
        }
        return $this->execute($command);
    }
    /**
     * Send a notification using kdialog.
     */
    protected function send_linux_k_dialog(): bool
    {
        $message = $this->body !== '' ? "{$this->title}: {$this->body}" : $this->title;
        return $this->execute(['kdialog', '--passivepopup', $message, '5', '--title', $this->title]);
    }
    /**
     * Execute a command and return whether it was successful.
     *
     * @param  array<int, string>  $command
     */
    protected function execute(array $command): bool
    {
        $process = new Process($command);
        $process->run();
        return $process->is_successful();
    }
    /**
     * Escape a string for use in AppleScript.
     */
    protected function escape_apple_script(string $value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\"'], $value) . '"';
    }
    /**
     * Send the notification.
     */
    public function display(): void
    {
        $this->prompt();
    }
    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}