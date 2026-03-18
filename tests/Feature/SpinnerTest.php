<?php

declare(strict_types=1);

use Laravel\Prompts\Prompt;

use function Laravel\Prompts\spin;

it('renders a spinner while executing a callback and then returns the value', function () {
    Prompt::fake();

    $result = spin(function () {
        usleep(1000);

        return 'done';
    }, 'Running...');

    expect($result)->toBe('done');

    Prompt::assertOutputContains('Running...');
});
