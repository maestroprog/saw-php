<?php

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    echo sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.');
}

require_once '../../src/bootstrap.php';
$controller = \Saw\Saw::instance()->
exit(0);
