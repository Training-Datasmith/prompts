<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Illuminate\Support\Collection;
class Suggest_Prompt extends Prompt
{
    use Concerns\Has_Info;
    use Concerns\Scrolling;
    use Concerns\Truncation;
    use Concerns\Typed_Value;
    /**
     * The options for the suggest prompt.
     *
     * @var array<string>|Closure(string): (array<string>|Collection<int, string>)
     */
    public array|Closure $options;
    /**
     * The cache of matches.
     *
     * @var array<string>|null
     */
    protected ?array $matches = null;
    /**
     * Create a new SuggestPrompt instance.
     *
     * @param  array<string>|Collection<int, string>|Closure(string): (array<string>|Collection<int, string>)  $options
     */
    public function __construct(public string $label, array|Collection|Closure $options, public string $placeholder = '', public string $default = '', public int $scroll = 5, public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null, public string|Closure $info = '')
    {
        $this->options = $options instanceof Collection ? $options->all() : $options;
        $this->initialize_scrolling();
        $this->on('key', fn(string $key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::SHIFT_TAB, Key::CTRL_P => $this->highlight_previous(count($this->matches()), true),
            Key::DOWN, Key::DOWN_ARROW, Key::TAB, Key::CTRL_N => $this->highlight_next(count($this->matches()), true),
            Key::one_of([Key::HOME, Key::CTRL_A], $key) => $this->highlighted !== null ? $this->highlight(0) : null,
            Key::one_of([Key::END, Key::CTRL_E], $key) => $this->highlighted !== null ? $this->highlight(count($this->matches()) - 1) : null,
            Key::ENTER => $this->select_highlighted(),
            Key::one_of([Key::LEFT, Key::LEFT_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::CTRL_B, Key::CTRL_F], $key) => $this->highlighted = null,
            default => (function (): void {
                $this->highlighted = null;
                $this->matches = null;
                $this->first_visible = 0;
            })(),
        });
        $this->track_typed_value($default, ignore: fn(string $key): bool => Key::one_of([Key::HOME, Key::END, Key::CTRL_A, Key::CTRL_E], $key) && $this->highlighted !== null);
    }
    /**
     * Get the value of the highlighted option.
     */
    public function highlighted_value(): ?string
    {
        if ($this->highlighted === null) {
            return null;
        }
        return $this->matches()[$this->highlighted] ?? null;
    }
    /**
     * Get the entered value with a virtual cursor.
     */
    public function value_with_cursor(int $max_width): string
    {
        if ($this->highlighted !== null) {
            return $this->value() === '' ? $this->dim($this->truncate($this->placeholder, $max_width)) : $this->truncate($this->value(), $max_width);
        }
        if ($this->value() === '') {
            return $this->dim($this->add_cursor($this->placeholder, 0, $max_width));
        }
        return $this->add_cursor($this->value(), $this->cursor_position, $max_width);
    }
    /**
     * Get options that match the input.
     *
     * @return array<string>
     */
    public function matches(): array
    {
        if (is_array($this->matches)) {
            return $this->matches;
        }
        if ($this->options instanceof Closure) {
            $matches = ($this->options)($this->value());
            return $this->matches = array_values($matches instanceof Collection ? $matches->all() : $matches);
        }
        return $this->matches = array_values(array_filter($this->options, fn(string $option) => str_starts_with(strtolower($option), strtolower($this->value()))));
    }
    /**
     * The current visible matches.
     *
     * @return array<string>
     */
    public function visible(): array
    {
        return array_slice($this->matches(), $this->first_visible, $this->scroll, preserve_keys: true);
    }
    /**
     * Select the highlighted entry.
     */
    protected function select_highlighted(): void
    {
        if ($this->highlighted === null) {
            return;
        }
        $this->typed_value = $this->matches()[$this->highlighted];
    }
}