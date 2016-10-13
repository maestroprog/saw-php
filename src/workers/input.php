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
    if (Init::init($config)) {
        out('configured. input...');
        if (!(Init::connect() or Init::start())) {
            out('Saw start failed');
            throw new \Exception('Framework starting fail');
        }
        out('work start');
        Init::work();
        out('work end');

        Init::stop();
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
