<?php

use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

return [
    'application' => [
        MyApplication::ID => [
            'class' => MyApplication::class,
            'arguments' => [
                'id' => '@appId',
                'multiThreadingProvider' => MultiThreadingProvider::class,
                'applicationMemory' => SharedMemoryInterface::class,
                'contextPool' => ContextPool::class,
            ],
        ],
    ],
    'controller' => [
//    'external_address' => '192.168.1.66', // внешний адрес, нужен при создании кластера
        'worker_multiplier' => 1,
        'worker_max_count' => 1,
        /* 'mediator' => [
            'enabled' => true, // включить поддержку посредника
            'auto_run' => true,
        ],*/
    ],
    'sockets' => require __DIR__ . '/../config/esockets_debug.php',
    'multiThreading' => [
        'disabled' => true,
    ],
];
