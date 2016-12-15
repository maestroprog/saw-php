<?php
/**
 ** Start main controller for php
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:22
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    echo sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.');
}

define('SAW_ENVIRONMENT', 'Controller');

$config = require __DIR__ . '/../config.php';
$controller = \maestroprog\saw\library\service\Controller::create($config);

\maestroprog\esockets\debug\Log::log('work start');
$controller->work();
\maestroprog\esockets\debug\Log::log('work end');

exit(0);
