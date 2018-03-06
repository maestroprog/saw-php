<?php

use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

return [
    'saw' => [
//        'debug' => true,
    ],
    'application' => [
        MyApplication::ID => [
            'class' => MyApplication::class,
            'arguments' => [
                'id' => '@appId',
                'multiThreadingProvider' => MultiThreadingProvider::class,
            ],
        ],
    ],
    'daemon' => [
        'controller_pid' => __DIR__ . '/../../controller.pid',
    ],
    'controller' => [
        'worker_multiplier' => 2,
        'worker_max_count' => 6,
    ],
    'sockets' => require __DIR__ . '/../config/esockets.php',
//    'sockets' => require __DIR__ . '/../config/esockets_debug.php',
    'multiThreading' => [
//        'disabled' => true,
    ],
];
