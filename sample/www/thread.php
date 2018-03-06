<?php

use Maestroprog\Saw\Helper\Debug;
use Maestroprog\Saw\Sample\Timings;
use Maestroprog\Saw\SawWeb;

require_once '../../vendor/autoload.php';

Timings::start('INDEX.PHP');

set_time_limit(1);
ini_set('log_errors', false);
\Esockets\Debug\Log::setEnv('WEB');

$sleep = 0;
$time = microtime(true);

Debug::enable();
$saw = new SawWeb(__DIR__ . '/../config.php');
$saw->thread()->thread('TEST1', function () {

});

var_dump('indexphp ' . ($t = microtime(true) - $time) * 1000);
var_dump('sleep ' . ($sleep) * 1000);
var_dump('without sleep ' . ($t - $sleep) * 1000);
var_dump('sleep percent : ' . ($sleep * 100 / $t));

Timings::dump();
