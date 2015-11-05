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
/*

$i = 0;
while (true) {
    $time = microtime(true);
    usleep(10000);
    if ($i % 100 == 0) {
        echo $i . ' : ' . number_format((microtime(true) - $time) * 1000, 4, '.', ' ') . ' ms' . PHP_EOL;
    }
    $i++;
}
exit;*/

echo 'input start' . PHP_EOL;

require_once '../src/input/input.php';

echo 'input end' . PHP_EOL;