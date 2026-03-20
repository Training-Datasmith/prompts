<?php

declare (strict_types=1);
namespace Laravel\Prompts;

class Key
{
    public const UP = "\x1b[A";
    public const SHIFT_UP = "\x1b[1;2A";
    public const PAGE_UP = "\x1b[5~";
    public const DOWN = "\x1b[B";
    public const SHIFT_DOWN = "\x1b[1;2B";
    public const PAGE_DOWN = "\x1b[6~";
    public const RIGHT = "\x1b[C";
    public const LEFT = "\x1b[D";
    public const UP_ARROW = "\x1bOA";
    public const DOWN_ARROW = "\x1bOB";
    public const RIGHT_ARROW = "\x1bOC";
    public const LEFT_ARROW = "\x1bOD";
    public const ESCAPE = "\x1b";
    public const DELETE = "\x1b[3~";
    public const BACKSPACE = "";
    public const ENTER = "\n";
    public const SPACE = ' ';
    public const TAB = "\t";
    public const SHIFT_TAB = "\x1b[Z";
    public const HOME = ["\x1b[1~", "\x1bOH", "\x1b[H", "\x1b[7~"];
    public const END = ["\x1b[4~", "\x1bOF", "\x1b[F", "\x1b[8~"];
    /**
     * Cancel/SIGINT
     */
    public const CTRL_C = "\x03";
    /**
     * Previous/Up
     */
    public const CTRL_P = "\x10";
    /**
     * Next/Down
     */
    public const CTRL_N = "\x0e";
    /**
     * Forward/Right
     */
    public const CTRL_F = "\x06";
    /**
     * Back/Left
     */
    public const CTRL_B = "\x02";
    /**
     * Backspace
     */
    public const CTRL_H = "\x08";
    /**
     * Home
     */
    public const CTRL_A = "\x01";
    /**
     * EOF
     */
    public const CTRL_D = "\x04";
    /**
     * End
     */
    public const CTRL_E = "\x05";
    /**
     * Negative affirmation
     */
    public const CTRL_U = "\x15";
    public const OPTION_BACKSPACE = "\x1b";
    /**
     * Checks for the constant values for the given match and returns the match
     *
     * @param  array<string|array<string>>  $keys
     */
    public static function one_of(array $keys, string $match): ?string
    {
        foreach ($keys as $key) {
            if (is_array($key) && static::one_of($key, $match) !== null) {
                return $match;
            }
            if ($key === $match) {
                return $match;
            }
        }
        return null;
    }
}