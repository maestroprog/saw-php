<?php
/**
 ** Saw entry gate file
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 19:01
 */


use maestroprog\Saw\Init;

define('SAW_ENVIRONMENT', 'Input');
$config = require __DIR__ . '/../config.php';

try {
    $init = Init::getInstance();
    if ($init->init($config)) {
        out('configured. input...');
        if (!($init->connect() or $init->start())) {
            out('Saw start failed');
            throw new \Exception('Framework starting fail');
        }
        out('work start');
        $init->work();
        out('work end');

        $init->stop();
        out('closed');
    }
} catch (Exception $e) {
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
