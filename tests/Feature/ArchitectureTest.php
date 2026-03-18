<?php

declare(strict_types=1);

arch("Doesn't use collections")
    ->expect('Laravel\Prompts')
    ->not->toUse(['collect']);
