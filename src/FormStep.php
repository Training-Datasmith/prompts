<?php

declare (strict_types=1);
namespace Laravel\Prompts;

use Closure;
class Form_Step
{
    protected readonly Closure $condition;
    public function __construct(protected readonly Closure $step, bool|Closure $condition, public readonly ?string $name, protected readonly bool $ignore_when_reverting)
    {
        $this->condition = is_bool($condition) ? fn(): bool => $condition : $condition;
    }
    /**
     * Execute this step.
     *
     * @param  array<mixed>  $responses
     */
    public function run(array $responses, mixed $previous_response): mixed
    {
        if (!$this->should_run($responses)) {
            return null;
        }
        return ($this->step)($responses, $previous_response, $this->name);
    }
    /**
     * Whether the step should run based on the given condition.
     *
     * @param  array<mixed>  $responses
     */
    protected function should_run(array $responses): bool
    {
        return ($this->condition)($responses);
    }
    /**
     * Whether this step should be skipped over when a subsequent step is reverted.
     *
     * @param  array<mixed>  $responses
     */
    public function should_ignore_when_reverting(array $responses): bool
    {
        if (!$this->should_run($responses)) {
            return true;
        }
        return $this->ignore_when_reverting;
    }
}