<?php

declare(strict_types=1);

/**
 * Example: Using laravel/prompts in a CLI application.
 *
 * laravel/prompts provides beautiful, type-safe interactive CLI inputs
 * with built-in validation, fallback support for non-interactive environments,
 * and a theme system for custom rendering.
 *
 * Install:
 *   composer require laravel/prompts
 *
 * Run this script from the terminal (requires an interactive TTY).
 */

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

require __DIR__ . '/../vendor/autoload.php';

// --- text(): single-line text input with optional validation ---
$name = text(
    label: 'What is your name?',
    placeholder: 'e.g. Alice',
    required: true,
    validate: fn(string $value) => strlen($value) < 2 ? 'Name must be at least 2 characters.' : null,
);
echo "Hello, $name!\n";

// --- password(): masked text input ---
$secret = password(
    label: 'Enter a password',
    required: true,
    validate: fn(string $value) => strlen($value) < 8 ? 'Password must be at least 8 characters.' : null,
);

// --- confirm(): yes/no confirmation ---
$proceed = confirm(
    label: 'Do you want to continue?',
    default: true,
);

if (! $proceed) {
    echo "Aborted.\n";
    exit(1);
}

// --- select(): single choice from a list ---
$env = select(
    label: 'Select an environment',
    options: ['local', 'staging', 'production'],
    default: 'local',
);

// --- multiselect(): multiple choices ---
$features = multiselect(
    label: 'Select features to enable',
    options: [
        'cache'    => 'Redis Cache',
        'queue'    => 'Job Queues',
        'search'   => 'Full-text Search',
        'storage'  => 'File Storage',
    ],
    default: ['cache'],
    required: true,
);

// --- suggest(): text input with autocomplete suggestions ---
$framework = suggest(
    label: 'Which PHP framework?',
    options: ['Laravel', 'Symfony', 'Slim', 'Lumen', 'CodeIgniter'],
    placeholder: 'Start typing...',
);

// --- search(): searchable list with dynamic filtering ---
$user = search(
    label: 'Search for a user',
    options: function (string $query): array {
        $users = ['alice@example.com', 'bob@example.com', 'carol@example.com'];
        return array_filter($users, fn($u) => str_contains($u, $query));
    },
);

// --- textarea(): multi-line text input ---
$notes = textarea(
    label: 'Add deployment notes',
    placeholder: 'Describe the changes...',
    rows: 5,
);

// --- note(): display an informational message box ---
note('Deployment configuration saved.', 'Info');

// --- table(): display tabular data ---
table(
    headers: ['Name', 'Environment', 'Status'],
    rows: [
        [$name, $env, 'Ready'],
        ['api-server', $env, 'Running'],
    ],
);

// --- progress(): display a progress bar ---
$results = progress(
    label: 'Processing items',
    steps: ['task-1', 'task-2', 'task-3'],
    callback: function (string $step): string {
        usleep(200_000); // Simulate work
        return "Processed: $step";
    },
);

// --- spin(): spinner while executing a slow callback ---
$data = spin(
    callback: function (): array {
        sleep(1); // Simulate API call
        return ['status' => 'ok'];
    },
    message: 'Fetching deployment status...',
);

var_dump($data);
