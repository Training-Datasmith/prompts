# Architecture: laravel/prompts

## Purpose

An interactive CLI prompt library for Laravel and PHP command-line applications. Provides
beautiful, user-friendly input prompts (text, password, select, multi-select, autocomplete,
confirm, number, search, spinner, progress bar, table, and forms) with fallback support
for non-interactive environments.

## Directory Structure

```
src/
  Prompt.php              # Abstract base class for all interactive prompt types
  Text_Prompt.php         # Single-line text input
  Password_Prompt.php     # Hidden text input
  Confirm_Prompt.php      # Yes/no confirmation
  Number_Prompt.php       # Numeric input with validation
  Select_Prompt.php       # Single-item selection from a list
  Multi_Select_Prompt.php # Multiple-item selection
  Auto_Complete_Prompt.php  # Text with autocomplete suggestions
  Search_Prompt.php       # Searchable single-item selection
  Multi_Search_Prompt.php # Searchable multi-item selection
  Suggest_Prompt.php      # Text input with suggestion hints
  Pause_Prompt.php        # Press-any-key pause
  Notify_Prompt.php       # Non-interactive notification display
  Spinner.php             # Animated spinner for long operations
  Progress.php            # Progress bar for iterable operations
  Note.php / Clear.php / Grid.php / Table.php / Stream.php / Task.php  # Display utilities
  Form_Builder.php / Form_Step.php  # Multi-step form orchestration

  Concerns/
    Colors.php        # ANSI color/style output methods
    Cursor.php        # Terminal cursor movement
    Erase.php         # Terminal erase operations
    Events.php        # Key press event handling
    Fakes_Input_Output.php  # Testing: fake input/output for unit tests
    Fallback.php      # Non-interactive fallback behavior
    Interactivity.php # Interactive mode detection and toggling
    Scrolling.php     # Scrollable list rendering
    Themes.php        # Theme registry: maps prompt types to renderer classes
    Truncation.php    # String truncation for terminal width
    Typed_Value.php   # Tracks the currently typed value character-by-character

  Exceptions/
    Form_Reverted_Exception.php           # Thrown when a form step is reverted (go back)
    Non_Interactive_Validation_Exception.php  # Thrown in fallback mode on validation failure

  Output/
    Buffered_Console_Output.php  # Collects output for diffing (only re-renders changed lines)
    Console_Output.php           # Symfony OutputInterface adapter

  Support/
    Logger.php   # Debug logger writing to a file (for development)
    Result.php   # Wrap/unwrap a prompt result value
    Utils.php    # Utility functions (ANSI stripping, width measurement)

  helpers.php    # Global helper functions: text(), select(), confirm(), etc.
  Key.php        # Key code constants (UP, DOWN, ENTER, BACKSPACE, etc.)
  Terminal.php   # Terminal size detection and raw mode control

playground/      # Runnable demo scripts for each prompt type
```

## Key Design Decisions

### Trait-Based Composition for Prompt

The `Prompt` base class uses a large set of traits (`Colors`, `Cursor`, `Events`, `Themes`, etc.)
to avoid a deep inheritance tree. Each concern is independently testable and composable.

### Theme System for Renderers

Each prompt type has an associated renderer (theme). `Themes::getRenderer()` returns the
renderer class for the current theme. This allows complete visual customization by
swapping renderer implementations without changing prompt logic.

### Buffered Rendering for Flicker-Free Updates

`Buffered_Console_Output` captures the full frame, diffs it against the previous frame,
and only re-writes changed lines. This produces smooth, flicker-free terminal updates
even for rapidly-updating prompts like `Progress` and `Spinner`.

### Faking for Tests

Prompts support fake input/output via `Prompt::fake()` and `Prompt::assertOutputContains()`.
Tests can simulate key presses and assert rendered output without a real terminal.

### Non-Interactive Fallback

When a TTY is not available (e.g., CI piped input), prompts fall back to simple `readline()`
or `fgets(STDIN)` based input, with validation errors thrown as exceptions.

## Extension Points

- **Custom prompt types** — extend `Prompt` and add a renderer to the theme.
- **Custom themes** — call `Prompt::addTheme()` to register alternative renderers.
- **Custom fallback behavior** — override `Prompt::fallback()` in a subclass.
