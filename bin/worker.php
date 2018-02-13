<?php
define('ENV', 'WORKER_' . getmygid());
// TODO add config for logging

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw worker must be run in cli mode.'));
}
if (!isset($argv[1]) || !file_exists($argv[1])) {
    die("\033[1;31mSaw worker must be configured with special config.\033[0m" . PHP_EOL);
}

require_once __DIR__ . '/../src/bootstrap.php';
\Maestroprog\Saw\Saw::instance()
    ->init($argv[1])
    ->instanceWorker()
    ->start();
