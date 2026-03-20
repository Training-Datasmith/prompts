<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

use Laravel\Prompts\Output\Buffered_Console_Output;
use Laravel\Prompts\Terminal;
use Php_Unit\Framework\Assert;
use RuntimeException;
trait Fakes_Input_Output
{
    /**
     * Fake the terminal and queue key presses to be simulated.
     *
     * @param  array<string>  $keys
     */
    public static function fake(array $keys = []): void
    {
        // Force interactive mode when testing because we will be mocking the terminal.
        static::interactive();
        $mock = \Mockery::mock(Terminal::class);
        $mock->should_receive('write')->by_default();
        $mock->should_receive('exit')->by_default();
        $mock->should_receive('setTty')->by_default();
        $mock->should_receive('restoreTty')->by_default();
        $mock->should_receive('cols')->by_default()->and_return(80);
        $mock->should_receive('lines')->by_default()->and_return(24);
        $mock->should_receive('initDimensions')->by_default();
        $mock->should_receive('supportsTrueColor')->by_default()->and_return(false);
        static::fake_key_presses($keys, function (string $key) use ($mock): void {
            $mock->should_receive('read')->once()->and_return($key);
        });
        static::$terminal = $mock;
        self::set_output(new Buffered_Console_Output());
    }
    /**
     * Implementation of the looping mechanism for simulating key presses.
     *
     * By ignoring the `$callable` parameter which contains the default logic
     * for simulating fake key presses, we can use a custom implementation
     * to emit key presses instead, allowing us to use different inputs.
     *
     * @param  array<string>  $keys
     * @param  callable(string $key): void  $callable
     */
    public static function fake_key_presses(array $keys, callable $callable): void
    {
        foreach ($keys as $key) {
            $callable($key);
        }
    }
    /**
     * Assert that the output contains the given string.
     */
    public static function assert_output_contains(string $string): void
    {
        Assert::assert_string_contains_string($string, static::content());
    }
    /**
     * Assert that the output doesn't contain the given string.
     */
    public static function assert_output_doesnt_contain(string $string): void
    {
        Assert::assert_string_not_contains_string($string, static::content());
    }
    /**
     * Assert that the stripped output contains the given string.
     */
    public static function assert_stripped_output_contains(string $string): void
    {
        Assert::assert_string_contains_string($string, static::stripped_content());
    }
    /**
     * Assert that the stripped output doesn't contain the given string.
     */
    public static function assert_stripped_output_doesnt_contain(string $string): void
    {
        Assert::assert_string_not_contains_string($string, static::stripped_content());
    }
    /**
     * Get the buffered console output.
     */
    public static function content(): string
    {
        if (!static::output() instanceof Buffered_Console_Output) {
            throw new RuntimeException('Prompt must be faked before accessing content.');
        }
        return static::output()->content();
    }
    /**
     * Get the buffered console output, stripped of escape sequences.
     */
    public static function stripped_content(): string
    {
        return preg_replace("/\x1b\\[[0-9;?]*[A-Za-z]/", '', static::content());
    }
}