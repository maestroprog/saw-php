<?php

ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.'));
}
try {
    require_once __DIR__ . '/../src/bootstrap.php';
    \Saw\Saw::instance()
        ->init(require __DIR__ . '/../sample/config/saw.php')
        ->instanceController()
        ->start();
} catch (\Throwable $e) {
    file_put_contents('dump.log', $e->getMessage() . PHP_EOL . $e->getTraceAsString());
}