<?php

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.'));
}

require_once '../src/bootstrap.php';
$controller = \Saw\Saw::instance()
    ->init(require '../sample/config/saw.php')
    ->instanceController()
    ->start();
