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

define('SAW_ENVIRONMENT', 'Worker');

$config = require __DIR__ . '/../config.php';

try {
    $worker = \maestroprog\saw\service\Worker::create($config);
} catch (Throwable $e) {
    \maestroprog\esockets\debug\Log::log($e->getMessage());
    exit(1);
}

\maestroprog\esockets\debug\Log::log('work start');
$worker->work();
\maestroprog\esockets\debug\Log::log('work end');

exit(0);
