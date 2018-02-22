<?php

use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

return [
    'application' => [
        MyApplication::ID => [
            'class' => MyApplication::class,
            'arguments' => [
                'id' => '@appId',
                'multiThreadingProvider' => MultiThreadingProvider::class,
            ],
        ],
    ],
    'controller' => [
        'worker_multiplier' => 1,
        'worker_max_count' => 1,
    ],
    'sockets' => require __DIR__ . '/../config/esockets_debug.php',
    'multiThreading' => [
        'disabled' => true,
    ],
];
