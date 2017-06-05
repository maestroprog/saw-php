<?php

use Saw\Connector\Main;
use Saw\Saw;

ini_set('display_errors', false);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');

$time = microtime(true);
require_once '../../src/bootstrap.php';
require_once '../../vendor/autoload.php';

Saw::getInstance()
    ->init(require __DIR__ . '/../config/common.php')
    ->run();

try {
    /**
     * @var Main $init
     */
    $init = Main::create($config);
} catch (Throwable $e) {
    header('HTTP/1.1 503 Service Unavailable');
    echo sprintf('<p style="color:red">%s</p>', $e->getMessage());
    if ($e->getPrevious()) {
        echo sprintf('<p style="color:deeppink">%s</p>', $e->getPrevious()->getMessage());
    }
    exit;
}

$time2 = microtime(true);
$init->work();
$mtrue = microtime(true);
$time22 = $mtrue - $time2;
$time3 = $mtrue - $time;
$time33 = $time3 - $time22;

foreach (['time22', 'time3', 'time33'] as $key) {
    var_dump(number_format($$key, 6, '.', ','));
}
