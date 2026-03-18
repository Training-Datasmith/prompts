<?php

declare(strict_types=1);

use Laravel\Prompts\Note;

use function Laravel\Prompts\note;

use Laravel\Prompts\Prompt;

it('renders a note', function () {
    Prompt::fake();

    note('Hello, World!');

    Prompt::assertOutputContains('Hello, World!');
});

it('can fall back', function () {
    Prompt::fallbackWhen(true);

    Note::fallbackUsing(function (Note $note) {
        expect($note->message)->toBe('Hello, World!');

        return true;
    });

    $result = (new Note('Hello, World!'))->display();

    expect($result)->toBeNull();
});
