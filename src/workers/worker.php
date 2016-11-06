<?php
/**
 * Saw entry worker script.
 * Created by PhpStorm.
 * User: Руслан
 * Date: 29.10.2016
 * Time: 18:54
 */

use maestroprog\Saw\Worker;

define('SAW_ENVIRONMENT', 'Input');
$config = require __DIR__ . '/../config.php';

try {
    $init = Worker::getInstance();
    if ($init->init($config)) {
        out('configured. input...');
        if (!($init->connect())) {
            out('Worker start failed');
            throw new \Exception('Worker starting fail');
        }
        register_shutdown_function(function () use ($init) {
            out('work start');
            //$init->work();
            out('work end');

            $init->stop();
            out('closed');
        });
        return \maestroprog\Saw\Task::getInstance()->setController($init);
    }
} catch (Throwable $e) {
    switch (PHP_SAPI) {
        case 'cli':
            out('Controller temporarily unavailable');
            out($e->getMessage());
            break;
        default:
            header('HTTP/1.1 503 Service Unavailable');
            echo sprintf('<p style="color:red">%s</p>', $e->getMessage());
    }
}
return false;
