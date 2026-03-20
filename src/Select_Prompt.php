<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;
class Select_Prompt extends Prompt
{
    use Concerns\Has_Info;
    use Concerns\Scrolling;
    /**
     * The options for the select prompt.
     *
     * @var array<int|string, string>
     */
    public array $options;
    /**
     * Create a new SelectPrompt instance.
     *
     * @param  array<int|string, string>|Collection<int|string, string>  $options
     */
    public function __construct(public string $label, array|Collection $options, public int|string|null $default = null, public int $scroll = 5, public mixed $validate = null, public string $hint = '', public bool|string $required = true, public ?Closure $transform = null, public string|Closure $info = '')
    {
        if ($this->required === false) {
            throw new InvalidArgumentException('Argument [required] must be true or a string.');
        }
        $this->options = $options instanceof Collection ? $options->all() : $options;
        if ($this->default !== null) {
            if (array_is_list($this->options)) {
                $this->initialize_scrolling(array_search($this->default, $this->options) ?: 0);
            } else {
                $this->initialize_scrolling(array_search($this->default, array_keys($this->options)) ?: 0);
            }
            $this->scroll_to_highlighted(count($this->options));
        } else {
            $this->initialize_scrolling(0);
        }
        $this->on('key', fn(string $key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, Key::CTRL_P, Key::CTRL_B, 'k', 'h' => $this->highlight_previous(count($this->options)),
            Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, Key::CTRL_N, Key::CTRL_F, 'j', 'l' => $this->highlight_next(count($this->options)),
            Key::one_of([Key::HOME, Key::CTRL_A], $key) => $this->highlight(0),
            Key::one_of([Key::END, Key::CTRL_E], $key) => $this->highlight(count($this->options) - 1),
            Key::ENTER => $this->submit(),
            default => null,
        });
    }
    /**
     * Get the value of the highlighted option.
     */
    public function highlighted_value(): int|string|null
    {
        if (array_is_list($this->options)) {
            return $this->options[$this->highlighted] ?? null;
        }
        return array_keys($this->options)[$this->highlighted] ?? null;
    }
    /**
     * Get the selected value.
     */
    public function value(): int|string|null
    {
        if (static::$interactive === false) {
            return $this->default;
        }
        if (array_is_list($this->options)) {
            return $this->options[$this->highlighted] ?? null;
        }
        return array_keys($this->options)[$this->highlighted];
    }
    /**
     * Get the selected label.
     */
    public function label(): ?string
    {
        if (array_is_list($this->options)) {
            return $this->options[$this->highlighted] ?? null;
        }
        return $this->options[array_keys($this->options)[$this->highlighted]] ?? null;
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
     * Determine whether the given value is invalid when the prompt is required.
     */
    protected function is_invalid_when_required(mixed $value): bool
    {
        return $value === null;
    }
}