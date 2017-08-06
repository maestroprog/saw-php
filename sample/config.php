<?php

require_once __DIR__ . '/MyApplication.php';

return array_merge_recursive(
    require_once __DIR__ . '/../config/saw.php',
    [
        'application' => [
            MyApplication::ID => [
                'class' => MyApplication::class,
                'arguments' => [
                    'id' => '@appId',
                    'multiThreadingProvider' => '!getMultiThreadingProvider',
                    'applicationMemory' => [
                        'method' => 'getSharedMemory',
                        'arguments' => [
                            'applicationId' => '@appId',
                        ]
                    ],
                    'contextPool' => '!getContextPool',
                ],
            ],
        ]
    ]
);
