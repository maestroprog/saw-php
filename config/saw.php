<?php

return [
    'saw' => [
        'debug' => true,
    ],
    'daemon' => [
        'controller_path' => __DIR__ . '/../bin/controller',
        'controller_pid' => (getcwd() ?: '.') . '/controller.pid',
//        'mediator_path' => __DIR__ . '/../../daemon/mediator.php',
        'worker_path' => __DIR__ . '/../bin/worker',
        'listen_address' => new \Esockets\Socket\Ipv4Address('0.0.0.0', 59090),
        'controller_address' => new \Esockets\Socket\Ipv4Address('127.0.0.1', 59090),
    ],
    'controller' => [
//    'external_address' => '192.168.1.66', // внешний адрес, нужен при создании кластера
        'worker_multiplier' => 1,
        'worker_max_count' => 4,
        /* 'mediator' => [
            'enabled' => true, // включить поддержку посредника
            'auto_run' => true,
        ],*/
    ],
    'application' => [],
    'sockets' => require __DIR__ . '/esockets.php',
    'multiThreading' => [
//        'disabled' => true,
    ]
];
