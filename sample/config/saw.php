<?php

return [
    'saw' => [
        'debug' => true,
    ],
    'daemon' => [
        'controller_path' => __DIR__ . '/../../daemon/controller.php',
        'controller_pid' => __DIR__ . '/controller.pid',
//        'mediator_path' => __DIR__ . '/../../daemon/mediator.php',
        'worker_path' => __DIR__ . '/../../daemon/worker.php',
        'worker_pid' => __DIR__ . '/worker.pid',
        'listen_address' => new \Esockets\socket\Ipv4Address('0.0.0.0', 59090),
        'controller_address' => new \Esockets\socket\Ipv4Address('127.0.0.1', 59090),
    ],
    'controller' => [
//    'external_address' => '192.168.1.66', // внешний адрес, нужен при создании кластера
        'worker_multiplier' => 4,
        'worker_max' => 8,
        /* todo 'mediator' => [
            'enabled' => true, // включить поддержку посредника
            'auto_run' => true,
        ],*/
    ],
    'application' => [
        'saw.sample.www' => [
            'class' => MyApplication::class,
            'arguments' => [
                'id' => 'saw.sample.www',
                'threadRunner' => '!getWebThreadRunner',
                'applicationMemory' => [
                    'method' => 'getSharedMemory',
                    'arguments' => [
                        'applicationId' => 'saw.sample.www',
                    ]
                ],
            ],
        ],
    ],
    'factory' => require __DIR__ . '/factory.php',
    'sockets' => require __DIR__ . '/esockets.php',
];
