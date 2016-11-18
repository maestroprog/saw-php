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


require __DIR__ . DIRECTORY_SEPARATOR . '../autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . '../vendors/maestroprog/esockets/autoload.php';

echo 'input start' . PHP_EOL;
try {
    $config = require __DIR__ . '/../src/config.php';
    $task = \maestroprog\Saw\Factory::getInstance()->configure($config)->createInput();
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
    exit;
}
echo 'input end' . PHP_EOL;

$time2 = microtime(true);

require_once 'App.php';
$app = new App();
$app->run($task);

$mtrue = microtime(true);
$time22 = $mtrue - $time2;
$time3 = $mtrue - $time;
$time33 = $time3 - $time22;

foreach (['time22', 'time3', 'time33'] as $key) {
    var_dump(number_format($$key, 6, '.', ','));
}
