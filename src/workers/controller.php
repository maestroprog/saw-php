<?php
/**
 ** Start main controller for php
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:22
 */

use maestroprog\Saw\Controller;

define('SAW_ENVIRONMENT', 'Controller');

$config = require __DIR__ . '/../config.php';
$controller = Controller::getInstance();
if ($controller->init($config)) {
    out('configured. start...');
    $controller->start() or (out('Saw start failed') or exit);
    out('start end');
    if (extension_loaded('pcntl')) {
        pcntl_signal(SIGINT, function ($sig) use ($controller) {
            $controller->work = false;
        });
        $controller->dispatch_signals = true;
    }
    out('work start');
    $controller->work();
    out('work end');
}
