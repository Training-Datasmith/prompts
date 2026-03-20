<?php

declare(strict_types=1);

use function Laravel\Prompts\clear;

use Laravel\Prompts\Prompt;

it('clears', function () {
    Prompt::fake();

    clear();

    Prompt::assertOutputContains("\033[H\033[J");
});
