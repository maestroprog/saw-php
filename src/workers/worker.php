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

\maestroprog\Saw\Factory::getInstance()->configure($config)->createWorker();
try {

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
