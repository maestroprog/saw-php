<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 17:48
 */

$time = microtime(true);

ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('error_log', 'e.log');
ini_set('log_errors', true);

$config = require __DIR__ . '/config.php';

out('input start');
try {
    $init = \maestroprog\Saw\Factory::getInstance()->configure($config)->createInput();
} catch (Throwable $e) {
    header('HTTP/1.1 503 Service Unavailable');
    echo sprintf('<p style="color:red">%s</p>', $e->getMessage());
    exit;
}
out('input end');

$time2 = microtime(true);

$init->run();

$mtrue = microtime(true);
$time22 = $mtrue - $time2;
$time3 = $mtrue - $time;
$time33 = $time3 - $time22;

foreach (['time22', 'time3', 'time33'] as $key) {
    var_dump(number_format($$key, 6, '.', ','));
}
