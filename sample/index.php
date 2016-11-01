<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 17:48
 */

ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('error_log', 'e.log');
ini_set('log_errors', true);


require '../autoload.php';
require '../vendors/maestroprog/esockets/autoload.php';

echo 'input start' . PHP_EOL;

/**
 * @var $init \maestroprog\Saw\Init
 */
if (!($init = require_once '../src/workers/input.php')) {
    throw new Exception('Cannot init!');
}

echo 'input end' . PHP_EOL;

$init::run(function () {
    for ($i = 0; $i < 10000; $i++) {

    }
}, 'MODULE_1_INIT');

$init::run(function () {
    for ($i = 0; $i < 10000; $i++) {

    }
}, 'MODULE_2_INIT');

$init::run(function () {
    for ($i = 0; $i < 10000; $i++) {

    }
}, 'MODULE_3_INIT');

