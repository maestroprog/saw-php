<?php

use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Saw;

require_once '../../vendor/autoload.php';

set_time_limit(1);
ini_set('log_errors', false);
\Esockets\Debug\Log::setEnv('WEB');

$time = microtime(true);

Saw::instance()
    ->init(__DIR__ . '/../config.php')
    ->instanceApp(MyApplication::class)
    ->run();

var_dump((microtime(true) - $time) * 1000, 'ms');
