<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
use Illuminate\Support\Collection;
class Auto_Complete_Prompt extends Prompt
{
    use Concerns\Typed_Value;
    /**
     * The options for the autocomplete prompt.
     *
     * @var array<string>|Closure(string): (array<string>|Collection<int, string>)
     */
    public array|Closure $options;
    protected string $match = '';
    protected int $highlighted = 0;
    /**
     * @var array<string>|null
     */
    protected ?array $matches = null;
    /**
     * Create a new AutoCompletePrompt instance.
     *
     * @param  array<string>|Collection<int, string>|Closure(string): (array<string>|Collection<int, string>)  $options
     */
    public function __construct(public string $label, array|Collection|Closure $options = [], public string $placeholder = '', public string $default = '', public bool|string $required = false, public mixed $validate = null, public string $hint = '', public ?Closure $transform = null)
    {
        $this->options = $options instanceof Collection ? $options->all() : $options;
        $this->on('key', function ($key): void {
            if (in_array($key, [Key::UP, Key::UP_ARROW])) {
                $matches = $this->matches();
                if (count($matches) > 0) {
                    $this->highlighted = ($this->highlighted - 1 + count($matches)) % count($matches);
                }
                return;
            }
            if (in_array($key, [Key::DOWN, Key::DOWN_ARROW])) {
                $matches = $this->matches();
                if (count($matches) > 0) {
                    $this->highlighted = ($this->highlighted + 1) % count($matches);
                }
                return;
            }
            if ($key === Key::TAB && $this->cursor_position >= mb_strlen($this->typed_value)) {
                $match = $this->get_match();
                if ($match !== '' && mb_strlen($match) > mb_strlen($this->value())) {
                    // Ghost text is showing — accept it
                    $this->typed_value = $match;
                    $this->cursor_position = mb_strlen($match);
                } else {
                    // No ghost text — request suggestions
                    $this->matches = null;
                    $this->highlighted = 0;
                }
                return;
            }
            if (in_array($key, [Key::RIGHT, Key::RIGHT_ARROW]) && $this->cursor_position >= mb_strlen($this->typed_value)) {
                $match = $this->get_match();
                if ($match !== '') {
                    $this->typed_value = $match;
                    $this->cursor_position = mb_strlen($match);
                }
                return;
            }
            // Any other key resets the highlight and match cache
            $this->highlighted = 0;
            $this->matches = null;
        });
        $this->track_typed_value($default, ignore: fn($key): bool => in_array($key, [Key::UP, Key::UP_ARROW, Key::DOWN, Key::DOWN_ARROW]));
    }
    /**
     * Get the entered value with a virtual cursor.
     */
    public function value_with_cursor(int $max_width): string
    {
        if ($this->value() === '') {
            return $this->dim($this->add_cursor($this->placeholder, 0, $max_width));
        }
        $this->match = $this->get_match();
        $ghost_text = '';
        if ($this->match !== '' && mb_strlen($this->match) > mb_strlen($this->value())) {
            $ghost_text = mb_substr($this->match, mb_strlen($this->value()));
        }
        // When cursor is at the end and there's ghost text, make the first
        // ghost character the inverted cursor so it flows naturally.
        if ($ghost_text !== '' && $this->cursor_position >= mb_strlen($this->value())) {
            $cursor_char = mb_substr($ghost_text, 0, 1);
            $remaining_ghost = mb_substr($ghost_text, 1);
            return $this->value() . $this->inverse($cursor_char) . $this->dim($remaining_ghost);
        }
        return $this->add_cursor($this->value(), $this->cursor_position, $max_width) . $this->dim($ghost_text);
    }
    /**
     * Get the current matches for the typed value.
     *
     * @return array<string>
     */
    public function matches(): array
    {
        if (is_array($this->matches)) {
            return $this->matches;
        }
        if ($this->options instanceof Closure) {
            $options = ($this->options)($this->value());
            return $this->matches = array_values($options instanceof Collection ? $options->all() : $options);
        }
        return $this->matches = array_values(array_filter($this->options, fn(string $option): bool => str_starts_with(strtolower($option), strtolower($this->value()))));
    }
    /**
     * Get the current match.
     */
    protected function get_match(): string
    {
        return $this->matches()[$this->highlighted] ?? '';
    }
}