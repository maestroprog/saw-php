<?php

use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Saw;

require_once '../../vendor/autoload.php';

Saw::instance()
    ->init(__DIR__ . '/../config.php')
    ->instanceApp(MyApplication::class)
    ->run();
