<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Illuminate\Support\Collection;
class Multi_Select_Prompt extends Prompt
{
    use Concerns\Has_Info;
    use Concerns\Scrolling;
    /**
     * The options for the multi-select prompt.
     *
     * @var array<int|string, string>
     */
    public array $options;
    /**
     * The default values the multi-select prompt.
     *
     * @var array<int|string>
     */
    public array $default;
    /**
     * The selected values.
     *
     * @var array<int|string>
     */
    protected array $values = [];
    /**
     * Create a new MultiSelectPrompt instance.
     *
     * @param  array<int|string, string>|Collection<int|string, string>  $options
     * @param  array<int|string>|Collection<int, int|string>  $default
     */
    public function __construct(public string $label, array|Collection $options, array|Collection $default = [], public int $scroll = 5, public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null, public string|Closure $info = '')
    {
        $this->options = $options instanceof Collection ? $options->all() : $options;
        $this->default = $default instanceof Collection ? $default->all() : $default;
        $this->values = $this->default;
        $this->initialize_scrolling(0);
        $this->on('key', fn(string $key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, Key::CTRL_P, Key::CTRL_B, 'k', 'h' => $this->highlight_previous(count($this->options)),
            Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, Key::CTRL_N, Key::CTRL_F, 'j', 'l' => $this->highlight_next(count($this->options)),
            Key::one_of(Key::HOME, $key) => $this->highlight(0),
            Key::one_of(Key::END, $key) => $this->highlight(count($this->options) - 1),
            Key::SPACE => $this->toggle_highlighted(),
            Key::CTRL_A => $this->toggle_all(),
            Key::ENTER => $this->submit(),
            default => null,
        });
    }
    /**
     * Get the value of the highlighted option.
     */
    public function highlighted_value(): int|string|null
    {
        if ($this->highlighted === null) {
            return null;
        }
        if (array_is_list($this->options)) {
            return $this->options[$this->highlighted] ?? null;
        }
        return array_keys($this->options)[$this->highlighted] ?? null;
    }
    /**
     * Get the selected values.
     *
     * @return array<int|string>
     */
    public function value(): array
    {
        return array_values($this->values);
    }
    /**
     * Get the selected labels.
     *
     * @return array<string>
     */
    public function labels(): array
    {
        if (array_is_list($this->options)) {
            return array_map(fn(int|string $value): string => (string) $value, $this->values);
        }
        return array_values(array_intersect_key($this->options, array_flip($this->values)));
    }
    /**
     * The currently visible options.
     *
     * @return array<int|string, string>
     */
    public function visible(): array
    {
        return array_slice($this->options, $this->first_visible, $this->scroll, preserve_keys: true);
    }
    /**
     * Check whether the value is currently highlighted.
     */
    public function is_highlighted(string $value): bool
    {
        if (array_is_list($this->options)) {
            return $this->options[$this->highlighted] === $value;
        }
        return array_keys($this->options)[$this->highlighted] === $value;
    }
    /**
     * Check whether the value is currently selected.
     */
    public function is_selected(string $value): bool
    {
        return in_array($value, $this->values);
    }
    /**
     * Toggle all options.
     */
    protected function toggle_all(): void
    {
        if (count($this->values) === count($this->options)) {
            $this->values = [];
        } else {
            $this->values = array_is_list($this->options) ? array_values($this->options) : array_keys($this->options);
        }
    }
    /**
     * Toggle the highlighted entry.
     */
    protected function toggle_highlighted(): void
    {
        $value = array_is_list($this->options) ? $this->options[$this->highlighted] : array_keys($this->options)[$this->highlighted];
        if (in_array($value, $this->values)) {
            $this->values = array_filter($this->values, fn($v): bool => $v !== $value);
        } else {
            $this->values[] = $value;
        }
    }
}