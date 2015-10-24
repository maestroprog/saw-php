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

//$c = file_get_contents('test.txt');
//vaR_dump(unpack('Nv0/cv1', $c));
//exit;

function out($message)
{
    echo $message . PHP_EOL;
}

out('input start');

require_once '../src/input/input.php';

out('input end');