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

/**
 * @var $task \maestroprog\Saw\Task
 */
if (!($task = require_once __DIR__ . DIRECTORY_SEPARATOR . '../src/workers/input.php')) {
    throw new Exception('Cannot init!');
}

echo 'input end' . PHP_EOL;

$time2 = microtime(true);

$task->run(function () {
    for ($i = 0; $i < 10000; $i++) {
        'nope';
    }
}, 'MODULE_1_INIT');

$task->run(function () {
    for ($i = 0; $i < 10000; $i++) {
        'nope';
    }
}, 'MODULE_2_INIT');

$task->run(function () {
    for ($i = 0; $i < 10000; $i++) {
        'nope';
    }
}, 'MODULE_3_INIT');

$mtrue = microtime(true);
$time22 = $mtrue - $time2;
$time3 = $mtrue - $time;
$time33 = $time3 - $time22;

foreach (['time22', 'time3', 'time33'] as $key) {
    var_dump(number_format($$key, 6, '.', ','));
}
