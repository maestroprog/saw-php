<?php

require_once '../../vendor/autoload.php';

set_time_limit(5);

$time = microtime(true);

$header = 1;
for ($i = 0; $i < 2; $i++) {
}
$article = $i;
for ($i = 0; $i < 3; $i++) {
}
$footer = $i;
for ($i = 0; $i < 2; $i++) {
}
$end = $i;

echo $header, $article, $footer, $end;
var_dump((microtime(true) - $time) * 1000, 'ms');
