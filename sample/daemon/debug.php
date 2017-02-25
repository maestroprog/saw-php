<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2015
 * Time: 17:02
 */

use maestroprog\saw\Connector\Main;

define('SAW_ENVIRONMENT', 'Debug');
$config = require __DIR__ . '/../common.php';

try {
    $init = Main::getInstance();
    if ($init->init($config)) {
        Esockets\debug\Log::log('configured. input...');
        if (!($init->connect() or $init->start())) {
            Esockets\debug\Log::log('Saw start failed');
            throw new \Exception('Framework starting fail');
        }
        Esockets\debug\Log::log('work start');
        $init->work();
        Esockets\debug\Log::log('input end');

        $init->stop();
        Esockets\debug\Log::log('closed');
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
