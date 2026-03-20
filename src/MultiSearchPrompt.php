<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Laravel\Prompts\Support\Utils;
class Multi_Search_Prompt extends Prompt
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
     * Whether the matches are initially a list.
     */
    protected bool $is_list;
    /**
     * The selected values.
     *
     * @var array<int|string, string>
     */
    public array $values = [];
    /**
     * Create a new MultiSearchPrompt instance.
     *
     * @param  Closure(string): array<int|string, string>  $options
     */
    public function __construct(public string $label, public Closure $options, public string $placeholder = '', public int $scroll = 5, public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null, public string|Closure $info = '')
    {
        $this->track_typed_value(submit: false, ignore: fn(string $key): bool => Key::one_of([Key::SPACE, Key::HOME, Key::END, Key::CTRL_A, Key::CTRL_E], $key) && $this->highlighted !== null);
        $this->initialize_scrolling();
        $this->on('key', fn(string $key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::SHIFT_TAB => $this->highlight_previous(count($this->matches), true),
            Key::DOWN, Key::DOWN_ARROW, Key::TAB => $this->highlight_next(count($this->matches), true),
            Key::one_of(Key::HOME, $key) => $this->highlighted !== null ? $this->highlight(0) : null,
            Key::one_of(Key::END, $key) => $this->highlighted !== null ? $this->highlight(count($this->matches()) - 1) : null,
            Key::SPACE => $this->highlighted !== null ? $this->toggle_highlighted() : null,
            Key::CTRL_A => $this->highlighted !== null ? $this->toggle_all() : null,
            Key::CTRL_E => null,
            Key::ENTER => $this->submit(),
            Key::LEFT, Key::LEFT_ARROW, Key::RIGHT, Key::RIGHT_ARROW => $this->highlighted = null,
            default => $this->search(),
        });
    }
    /**
     * Get the value of the highlighted option.
     */
    public function highlighted_value(): int|string|null
    {
        if ($this->highlighted === null || !is_array($this->matches)) {
            return null;
        }
        if ($this->is_list()) {
            return $this->matches[$this->highlighted] ?? null;
        }
        return array_keys($this->matches)[$this->highlighted] ?? null;
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
        $matches = ($this->options)($this->typed_value);
        if (!isset($this->is_list) && count($matches) > 0) {
            // This needs to be captured the first time we receive matches so
            // we know what we're dealing with later if matches is empty.
            $this->is_list = array_is_list($matches);
        }
        if (!isset($this->is_list)) {
            return $this->matches = [];
        }
        if (strlen($this->typed_value) > 0) {
            return $this->matches = $matches;
        }
        return $this->matches = $this->is_list ? [...array_diff(array_values($this->values), $matches), ...$matches] : array_diff($this->values, $matches) + $matches;
    }
    /**
     * The currently visible matches
     *
     * @return array<string>
     */
    public function visible(): array
    {
        return array_slice($this->matches(), $this->first_visible, $this->scroll, preserve_keys: true);
    }
    /**
     * Toggle all options.
     */
    protected function toggle_all(): void
    {
        $all_matches_selected = Utils::all_match($this->matches, fn($label, $key): bool => $this->is_list() ? array_key_exists($label, $this->values) : array_key_exists($key, $this->values));
        if ($all_matches_selected) {
            $this->values = array_filter($this->values, fn(string $value): bool => $this->is_list() ? !in_array($value, $this->matches) : !array_key_exists(array_search($value, $this->matches), $this->matches));
        } else {
            $this->values = $this->is_list() ? array_merge($this->values, array_combine(array_values($this->matches), array_values($this->matches))) : array_merge($this->values, array_combine(array_keys($this->matches), array_values($this->matches)));
        }
    }
    /**
     * Toggle the highlighted entry.
     */
    protected function toggle_highlighted(): void
    {
        if ($this->is_list()) {
            $label = $this->matches[$this->highlighted];
            $key = $label;
        } else {
            $key = array_keys($this->matches)[$this->highlighted];
            $label = $this->matches[$key];
        }
        if (array_key_exists($key, $this->values)) {
            unset($this->values[$key]);
        } else {
            $this->values[$key] = $label;
        }
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
     *
     * @return array<int|string>
     */
    public function value(): array
    {
        return array_keys($this->values);
    }
    /**
     * Get the selected labels.
     *
     * @return array<string>
     */
    public function labels(): array
    {
        return array_values($this->values);
    }
    /**
     * Whether the matches are initially a list.
     */
    public function is_list(): bool
    {
        return $this->is_list;
    }
}