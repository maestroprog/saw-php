<?php

use Esockets\Base\Exception\ConfiguratorException;
use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Saw;

require_once '../../vendor/autoload.php';

try {
    Saw::instance()
        ->init(__DIR__ . '/../config.php')
        ->instanceApp(MyApplication::class)
        ->run();
} catch (ConfiguratorException $e) {
} catch (ReflectionException $e) {
}
