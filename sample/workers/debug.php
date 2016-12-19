<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2015
 * Time: 17:02
 */

use maestroprog\saw\library\services\Init;

define('SAW_ENVIRONMENT', 'Debug');
$config = require __DIR__ . '/../config.php';

try {
    $init = Init::getInstance();
    if ($init->init($config)) {
        \maestroprog\esockets\debug\Log::log('configured. input...');
        if (!($init->connect() or $init->start())) {
            \maestroprog\esockets\debug\Log::log('Saw start failed');
            throw new \Exception('Framework starting fail');
        }
        \maestroprog\esockets\debug\Log::log('work start');
        $init->work();
        \maestroprog\esockets\debug\Log::log('input end');

        $init->stop();
        \maestroprog\esockets\debug\Log::log('closed');
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
