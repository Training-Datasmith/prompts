<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use InvalidArgumentException;
class Search_Prompt extends Prompt
{
    use Concerns\Has_Info;
    use Concerns\Scrolling;
    use Concerns\Truncation;
    use Concerns\Typed_Value;
    /**
     * The cached matches.
     *
     * @var array<int|string, string>|null
     */
    protected ?array $matches = null;
    /**
     * Create a new SearchPrompt instance.
     *
     * @param  Closure(string): array<int|string, string>  $options
     */
    public function __construct(public string $label, public Closure $options, public string $placeholder = '', public int $scroll = 5, public mixed $validate = null, public string $hint = '', public bool|string $required = true, public ?Closure $transform = null, public string|Closure $info = '')
    {
        if ($this->required === false) {
            throw new InvalidArgumentException('Argument [required] must be true or a string.');
        }
        $this->track_typed_value(submit: false, ignore: fn(string $key): bool => Key::one_of([Key::HOME, Key::END, Key::CTRL_A, Key::CTRL_E], $key) && $this->highlighted !== null);
        $this->initialize_scrolling();
        $this->on('key', fn(string $key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::SHIFT_TAB, Key::CTRL_P => $this->highlight_previous(count($this->matches), true),
            Key::DOWN, Key::DOWN_ARROW, Key::TAB, Key::CTRL_N => $this->highlight_next(count($this->matches), true),
            Key::one_of([Key::HOME, Key::CTRL_A], $key) => $this->highlighted !== null ? $this->highlight(0) : null,
            Key::one_of([Key::END, Key::CTRL_E], $key) => $this->highlighted !== null ? $this->highlight(count($this->matches()) - 1) : null,
            Key::ENTER => $this->highlighted !== null ? $this->submit() : $this->search(),
            Key::one_of([Key::LEFT, Key::LEFT_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::CTRL_B, Key::CTRL_F], $key) => $this->highlighted = null,
            default => $this->search(),
        });
    }
    /**
     * Get the value of the highlighted option.
     */
    public function highlighted_value(): int|string|null
    {
        return $this->value();
    }
    /**
     * Perform the search.
     */
    protected function search(): void
    {
        $this->state = 'searching';
        $this->highlighted = null;
        $this->render();
        $this->matches = null;
        $this->first_visible = 0;
        $this->state = 'active';
    }
    /**
     * Get the entered value with a virtual cursor.
     */
    public function value_with_cursor(int $max_width): string
    {
        if ($this->highlighted !== null) {
            return $this->typed_value === '' ? $this->dim($this->truncate($this->placeholder, $max_width)) : $this->truncate($this->typed_value, $max_width);
        }
        if ($this->typed_value === '') {
            return $this->dim($this->add_cursor($this->placeholder, 0, $max_width));
        }
        return $this->add_cursor($this->typed_value, $this->cursor_position, $max_width);
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
        return $this->matches = ($this->options)($this->typed_value);
    }
    /**
     * The currently visible matches.
     *
     * @return array<string>
     */
    public function visible(): array
    {
        return array_slice($this->matches(), $this->first_visible, $this->scroll, preserve_keys: true);
    }
    /**
     * Get the current search query.
     */
    public function search_value(): string
    {
        return $this->typed_value;
    }
    /**
     * Get the selected value.
     */
    public function value(): int|string|null
    {
        if ($this->matches === null || $this->highlighted === null) {
            return null;
        }
        return array_is_list($this->matches) ? $this->matches[$this->highlighted] : array_keys($this->matches)[$this->highlighted];
    }
    /**
     * Get the selected label.
     */
    public function label(): ?string
    {
        return $this->matches[array_keys($this->matches)[$this->highlighted]] ?? null;
    }
}