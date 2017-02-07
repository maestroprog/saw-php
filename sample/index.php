<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 17:48
 */

$time = microtime(true);

$config = require __DIR__ . '/config.php';

out('input start');
try {
    /**
     * @var maestroprog\saw\service\Init $init
     */
    $init = maestroprog\saw\service\Init::create($config);
} catch (Throwable $e) {
    header('HTTP/1.1 503 Service Unavailable');
    echo sprintf('<p style="color:red">%s</p>', $e->getMessage());
    if ($e->getPrevious()) {
        echo sprintf('<p style="color:deeppink">%s</p>', $e->getPrevious()->getMessage());
    }
    exit;
}
out('input end');

$time2 = microtime(true);
$init->work();
$mtrue = microtime(true);
$time22 = $mtrue - $time2;
$time3 = $mtrue - $time;
$time33 = $time3 - $time22;

foreach (['time22', 'time3', 'time33'] as $key) {
    var_dump(number_format($$key, 6, '.', ','));
}
