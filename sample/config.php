<?php

use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

require_once __DIR__ . '/MyApplication.php';

return array_merge_recursive(
    require_once __DIR__ . '/../config/saw.php',
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
