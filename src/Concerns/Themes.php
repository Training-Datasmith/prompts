<?php

declare (strict_types=1);
namespace Laravel\Prompts\Concerns;

use InvalidArgumentException;
use Laravel\Prompts\Auto_Complete_Prompt;
use Laravel\Prompts\Clear;
use Laravel\Prompts\Confirm_Prompt;
use Laravel\Prompts\Grid;
use Laravel\Prompts\Multi_Search_Prompt;
use Laravel\Prompts\Multi_Select_Prompt;
use Laravel\Prompts\Note;
use Laravel\Prompts\Number_Prompt;
use Laravel\Prompts\Password_Prompt;
use Laravel\Prompts\Pause_Prompt;
use Laravel\Prompts\Progress;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Search_Prompt;
use Laravel\Prompts\Select_Prompt;
use Laravel\Prompts\Spinner;
use Laravel\Prompts\Stream;
use Laravel\Prompts\Suggest_Prompt;
use Laravel\Prompts\Table;
use Laravel\Prompts\Task;
use Laravel\Prompts\Textarea_Prompt;
use Laravel\Prompts\Text_Prompt;
use Laravel\Prompts\Themes\Default\Auto_Complete_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Clear_Renderer;
use Laravel\Prompts\Themes\Default\Confirm_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Grid_Renderer;
use Laravel\Prompts\Themes\Default\Multi_Search_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Multi_Select_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Note_Renderer;
use Laravel\Prompts\Themes\Default\Number_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Password_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Pause_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Progress_Renderer;
use Laravel\Prompts\Themes\Default\Search_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Select_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Spinner_Renderer;
use Laravel\Prompts\Themes\Default\Stream_Renderer;
use Laravel\Prompts\Themes\Default\Suggest_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Table_Renderer;
use Laravel\Prompts\Themes\Default\Task_Renderer;
use Laravel\Prompts\Themes\Default\Textarea_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Text_Prompt_Renderer;
use Laravel\Prompts\Themes\Default\Title_Renderer;
use Laravel\Prompts\Title;
trait Themes
{
    /**
     * The name of the active theme.
     */
    protected static string $theme = 'default';
    /**
     * The available themes.
     *
     * @var array<string, array<class-string<Prompt>, class-string<object&callable>>>
     */
    protected static array $themes = ['default' => [Text_Prompt::class => Text_Prompt_Renderer::class, Number_Prompt::class => Number_Prompt_Renderer::class, Textarea_Prompt::class => Textarea_Prompt_Renderer::class, Password_Prompt::class => Password_Prompt_Renderer::class, Select_Prompt::class => Select_Prompt_Renderer::class, Multi_Select_Prompt::class => Multi_Select_Prompt_Renderer::class, Confirm_Prompt::class => Confirm_Prompt_Renderer::class, Pause_Prompt::class => Pause_Prompt_Renderer::class, Search_Prompt::class => Search_Prompt_Renderer::class, Multi_Search_Prompt::class => Multi_Search_Prompt_Renderer::class, Suggest_Prompt::class => Suggest_Prompt_Renderer::class, Spinner::class => Spinner_Renderer::class, Note::class => Note_Renderer::class, Table::class => Table_Renderer::class, Progress::class => Progress_Renderer::class, Clear::class => Clear_Renderer::class, Grid::class => Grid_Renderer::class, Auto_Complete_Prompt::class => Auto_Complete_Prompt_Renderer::class, Title::class => Title_Renderer::class, Stream::class => Stream_Renderer::class, Task::class => Task_Renderer::class]];
    /**
     * Get or set the active theme.
     *
     * @throws InvalidArgumentException
     */
    public static function theme(?string $name = null): string
    {
        if ($name === null) {
            return static::$theme;
        }
        if (!isset(static::$themes[$name])) {
            throw new InvalidArgumentException("Prompt theme [{$name}] not found.");
        }
        return static::$theme = $name;
    }
    /**
     * Add a new theme.
     *
     * @param  array<class-string<Prompt>, class-string<object&callable>>  $renderers
     */
    public static function add_theme(string $name, array $renderers): void
    {
        if ($name === 'default') {
            throw new InvalidArgumentException('The default theme cannot be overridden.');
        }
        static::$themes[$name] = $renderers;
    }
    /**
     * Get the renderer for the current prompt.
     */
    protected function get_renderer(): callable
    {
        $class = $this::class;
        return new (static::$themes[static::$theme][$class] ?? static::$themes['default'][$class])($this);
    }
    /**
     * Render the prompt using the active theme.
     */
    protected function render_theme(): string
    {
        $renderer = $this->get_renderer();
        return $renderer($this);
    }
}