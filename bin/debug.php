<?php


ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages-debug.log');
file_put_contents(ini_get('error_log'), '');

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.'));
}
if (!isset($argv[1]) || !file_exists($argv[1])) {
    die("\033[1;31mSaw debugger must be configured with special config.\033[0m" . PHP_EOL);
}

require_once __DIR__ . '/../src/bootstrap.php';
\Maestroprog\Saw\Saw::instance()
    ->init($argv[1])
    ->instanceDebugger()
    ->start();
