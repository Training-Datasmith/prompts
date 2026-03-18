<?php

declare(strict_types=1);

use function Laravel\Prompts\pause;

require __DIR__.'/../vendor/autoload.php';

$continued = pause();

var_dump($continued);
