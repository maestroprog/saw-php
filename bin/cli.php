<?php

defined('ENV') or define('ENV', 'UNKNOWN');

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 503 Service Unavailable');
    die(sprintf('<p style="color:red">%s</p>', 'Saw ' . ENV . ' must be run in cli mode.'));
}

$configFile = $argv[1] ?? __DIR__ . '/../config/saw.php';

if (!file_exists($configFile)) {
    echo "\033[1;31mSaw " . ENV . " must be configured with special config.\033[0m" . PHP_EOL;
    exit(1);
}


$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php'
];

foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        /** @noinspection PhpIncludeInspection */
        require_once $autoloadFile;
        break;
    }
}

\Esockets\Debug\Log::setEnv(ENV);

return $configFile;
