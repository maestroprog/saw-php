<?php

ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('log_errors', false);

//ini_set('error_log', __DIR__ . '/messages.log');
//file_put_contents(ini_get('error_log'), '');

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.'));
}
//try {
    require_once __DIR__ . '/../src/bootstrap.php';
    \Maestroprog\Saw\Saw::instance()
        ->init(__DIR__ . '/../config/saw.php')
        ->instanceController()
        ->start();
//} catch (\Throwable $e) {
    file_put_contents(__DIR__ . '/dump.log', $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
    file_put_contents(__DIR__ . '/dump.log', var_export($GLOBALS['log'], true), FILE_APPEND);
//}
