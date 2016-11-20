<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2015
 * Time: 17:02
 */

use maestroprog\Saw\Init;

define('SAW_ENVIRONMENT', 'Debug');
$config = require __DIR__ . '/config.php';

try {
    $init = Init::getInstance();
    if ($init->init($config)) {
        fputs(STDERR, 'configured. input...');
        if (!($init->connect() or $init->start())) {
            fputs(STDERR, 'Saw start failed');
            throw new \Exception('Framework starting fail');
        }
        fputs(STDERR, 'work start');
        $init->work();
        fputs(STDERR, 'input end');

        $init->stop();
        fputs(STDERR, 'closed');
    }
} catch (Exception $e) {
    switch (PHP_SAPI) {
        case 'cli':

            break;
        default:
            header('HTTP/1.1 503 Service Unavailable');
            echo sprintf('<p style="color:red">%s</p>', $e->getMessage());
    }
}
