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

require_once '../src/workers/input.php';

echo 'input end' . PHP_EOL;
