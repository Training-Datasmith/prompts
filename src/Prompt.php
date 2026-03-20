<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Laravel\Prompts\Exceptions\Form_Reverted_Exception;
use Laravel\Prompts\Output\Console_Output;
use Laravel\Prompts\Support\Result;
use RuntimeException;
use Symfony\Component\Console\Output\Output_Interface;
use Throwable;
abstract class Prompt
{
    use Concerns\Colors;
    use Concerns\Cursor;
    use Concerns\Erase;
    use Concerns\Events;
    use Concerns\Fakes_Input_Output;
    use Concerns\Fallback;
    use Concerns\Interactivity;
    use Concerns\Themes;
    /**
     * The current state of the prompt.
     */
    public string $state = 'initial';
    /**
     * The error message from the validator.
     */
    public string $error = '';
    /**
     * The cancel message displayed when this prompt is cancelled.
     */
    public string $cancel_message = 'Cancelled.';
    /**
     * The previously rendered frame.
     */
    protected string $prev_frame = '';
    /**
     * How many new lines were written by the last output.
     */
    protected int $new_lines_written = 1;
    /**
     * Whether user input is required.
     */
    public bool|string $required;
    /**
     * The transformation callback.
     */
    public ?Closure $transform = null;
    /**
     * The validator callback or rules.
     */
    public mixed $validate;
    /**
     * The cancellation callback.
     */
    protected static ?Closure $cancel_using;
    /**
     * Indicates if the prompt has been validated.
     */
    protected bool $validated = false;
    /**
     * The custom validation callback.
     */
    protected static ?Closure $validate_using;
    /**
     * The revert handler from the StepBuilder.
     */
    protected static ?Closure $revert_using = null;
    /**
     * The output instance.
     */
    protected static Output_Interface $output;
    /**
     * The terminal instance.
     */
    protected static Terminal $terminal;
    /**
     * Get the value of the prompt.
     */
    abstract public function value(): mixed;
    /**
     * Render the prompt and listen for input.
     */
    public function prompt(): mixed
    {
        try {
            $this->capture_previous_new_lines();
            if (static::should_fallback()) {
                return $this->fallback();
            }
            static::$interactive ??= stream_isatty(STDIN);
            if (!static::$interactive) {
                return $this->default();
            }
            $this->check_environment();
            try {
                static::terminal()->set_tty('-icanon -isig -echo');
            } catch (Throwable $e) {
                static::output()->writeln("<comment>{$e->get_message()}</comment>");
                static::fallback_when(true);
                return $this->fallback();
            }
            $this->hide_cursor();
            $this->render();
            return $this->run_loop(function (string $key): ?Result {
                $continue = $this->handle_key_press($key);
                $this->render();
                if ($continue === false || $key === Key::CTRL_C) {
                    if ($key === Key::CTRL_C) {
                        if (isset(static::$cancel_using)) {
                            return Result::from((static::$cancel_using)());
                        }
                        static::terminal()->exit();
                    }
                    if ($key === Key::CTRL_U && self::$revert_using) {
                        throw new Form_Reverted_Exception();
                    }
                    return Result::from($this->transformed_value());
                }
                // Continue looping.
                return null;
            });
        } finally {
            $this->clear_listeners();
        }
    }
    /**
     * Implementation of the prompt looping mechanism.
     *
     * @param  callable(string $key): ?Result  $callable
     */
    public function run_loop(callable $callable): mixed
    {
        while (($key = static::terminal()->read()) !== null) {
            /**
             * If $key is an empty string, Terminal::read
             * has failed. We can continue to the next
             * iteration of the loop, and try again.
             */
            if ($key === '') {
                continue;
            }
            $result = $callable($key);
            if ($result instanceof Result) {
                return $result->value;
            }
        }
    }
    /**
     * Register a callback to be invoked when a user cancels a prompt.
     */
    public static function cancel_using(?Closure $callback): void
    {
        static::$cancel_using = $callback;
    }
    /**
     * How many new lines were written by the last output.
     */
    public function new_lines_written(): int
    {
        return $this->new_lines_written;
    }
    /**
     * Capture the number of new lines written by the last output.
     */
    protected function capture_previous_new_lines(): void
    {
        $this->new_lines_written = method_exists(static::output(), 'newLinesWritten') ? static::output()->new_lines_written() : 1;
    }
    /**
     * Set the output instance.
     */
    public static function set_output(Output_Interface $output): void
    {
        self::$output = $output;
    }
    /**
     * Get the current output instance.
     */
    protected static function output(): Output_Interface
    {
        return self::$output ??= new Console_Output();
    }
    /**
     * Write output directly, bypassing newline capture.
     */
    protected static function write_directly(string $message): void
    {
        match (true) {
            method_exists(static::output(), 'writeDirectly') => static::output()->write_directly($message),
            method_exists(static::output(), 'getOutput') => static::output()->get_output()->write($message),
            default => static::output()->write($message),
        };
    }
    /**
     * Get the terminal instance.
     */
    public static function terminal(): Terminal
    {
        return static::$terminal ??= new Terminal();
    }
    /**
     * Set the custom validation callback.
     */
    public static function validate_using(Closure $callback): void
    {
        static::$validate_using = $callback;
    }
    /**
     * Revert the prompt using the given callback.
     *
     * @internal
     */
    public static function revert_using(Closure $callback): void
    {
        static::$revert_using = $callback;
    }
    /**
     * Clear any previous revert callback.
     *
     * @internal
     */
    public static function prevent_reverting(): void
    {
        static::$revert_using = null;
    }
    /**
     * Render the prompt.
     */
    protected function render(): void
    {
        static::terminal()->init_dimensions();
        $frame = $this->render_theme();
        if ($frame === $this->prev_frame) {
            return;
        }
        if ($this->state === 'initial') {
            static::output()->write($frame);
            $this->state = 'active';
            $this->prev_frame = $frame;
            return;
        }
        $terminal_height = static::terminal()->lines();
        $previous_frame_height = count(explode(PHP_EOL, $this->prev_frame));
        $renderable_lines = array_slice(explode(PHP_EOL, $frame), abs(min(0, $terminal_height - $previous_frame_height)));
        $this->move_cursor_to_column(1);
        $this->move_cursor_up(min($terminal_height, $previous_frame_height) - 1);
        $this->erase_down();
        static::output()->write(implode(PHP_EOL, $renderable_lines));
        $this->prev_frame = $frame;
    }
    /**
     * Submit the prompt.
     */
    protected function submit(): void
    {
        $this->validate($this->transformed_value());
        if ($this->state !== 'error') {
            $this->state = 'submit';
        }
    }
    /**
     * Handle a key press and determine whether to continue.
     */
    private function handle_key_press(string $key): bool
    {
        if ($this->state === 'error') {
            $this->state = 'active';
        }
        $this->emit('key', $key);
        if ($this->state === 'submit') {
            return false;
        }
        if ($key === Key::CTRL_U) {
            if (!self::$revert_using) {
                $this->state = 'error';
                $this->error = 'This cannot be reverted.';
                return true;
            }
            $this->state = 'cancel';
            $this->cancel_message = 'Reverted.';
            call_user_func(self::$revert_using);
            return false;
        }
        if ($key === Key::CTRL_C) {
            $this->state = 'cancel';
            return false;
        }
        if ($this->validated) {
            $this->validate($this->transformed_value());
        }
        return true;
    }
    /**
     * Transform the input.
     */
    private function transform(mixed $value): mixed
    {
        if (is_null($this->transform)) {
            return $value;
        }
        return call_user_func($this->transform, $value);
    }
    /**
     * Get the transformed value of the prompt.
     */
    protected function transformed_value(): mixed
    {
        return $this->transform($this->value());
    }
    /**
     * Validate the input.
     */
    private function validate(mixed $value): void
    {
        $this->validated = true;
        if ($this->required !== false && $this->is_invalid_when_required($value)) {
            $this->state = 'error';
            $this->error = is_string($this->required) && strlen($this->required) > 0 ? $this->required : 'Required.';
            return;
        }
        if (!isset($this->validate) && !isset(static::$validate_using)) {
            return;
        }
        $error = match (true) {
            is_callable($this->validate) => ($this->validate)($value),
            isset(static::$validate_using) => (static::$validate_using)($this),
            default => throw new RuntimeException('The validation logic is missing.'),
        };
        if (!is_string($error) && !is_null($error)) {
            throw new RuntimeException('The validator must return a string or null.');
        }
        if (is_string($error) && strlen($error) > 0) {
            $this->state = 'error';
            $this->error = $error;
        }
    }
    /**
     * Determine whether the given value is invalid when the prompt is required.
     */
    protected function is_invalid_when_required(mixed $value): bool
    {
        return $value === '' || $value === [] || $value === false || $value === null;
    }
    /**
     * Check whether the environment can support the prompt.
     */
    private function check_environment(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            throw new RuntimeException('Prompts is not currently supported on Windows. Please use WSL or configure a fallback.');
        }
    }
    /**
     * Restore the cursor and terminal state.
     */
    public function __destruct()
    {
        $this->restore_cursor();
        static::terminal()->restore_tty();
    }
}