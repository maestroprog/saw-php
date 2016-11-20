<?php
/**
 * Saw entry worker script.
 * Created by PhpStorm.
 * User: Руслан
 * Date: 29.10.2016
 * Time: 18:54
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    echo sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.');
}

define('SAW_ENVIRONMENT', 'Input');

$config = require __DIR__ . '/config.php';

try {
    $worker = \maestroprog\Saw\Factory::getInstance()->configure($config)->createWorker();
} catch (Throwable $e) {
    fputs(STDERR, $e->getMessage());
    exit(1);
}

fputs(STDERR, 'work start');
$worker->work();
fputs(STDERR, 'work end');

exit(0);
