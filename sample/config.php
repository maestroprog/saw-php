<?php

use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Sample\MyApplication;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

return array_merge_recursive(
    require __DIR__ . '/../config/saw.php',
    [
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
        ]
    ]
);
