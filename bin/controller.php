<?php

define('ENV', 'CONTROLLER');
// TODO add config for logging

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw controller must be run in cli mode.'));
}
if (!isset($argv[1]) || !file_exists($argv[1])) {
    echo "\033[1;31mSaw controller must be configured with special config.\033[0m" . PHP_EOL;
    exit(1);
}
require_once __DIR__ . '/../src/bootstrap.php';
\Maestroprog\Saw\Saw::instance()
    ->init($argv[1])
    ->instanceController()
    ->start();
