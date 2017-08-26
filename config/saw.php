<?php

return [
    'saw' => [
        'debug' => true,
    ],
    'daemon' => [
        'controller_path' => __DIR__ . '/../bin/controller.php',
        'controller_pid' => __DIR__ . '/controller.pid',
//        'mediator_path' => __DIR__ . '/../../daemon/mediator.php',
        'worker_path' => __DIR__ . '/../bin/worker.php',
        'listen_address' => new \Esockets\socket\Ipv4Address('0.0.0.0', 59090),
        'controller_address' => new \Esockets\socket\Ipv4Address('127.0.0.1', 59090),
    ],
    'controller' => [
//    'external_address' => '192.168.1.66', // внешний адрес, нужен при создании кластера
        'worker_multiplier' => 4,
        'worker_max_count' => 8,
        /* 'mediator' => [
            'enabled' => true, // включить поддержку посредника
            'auto_run' => true,
        ],*/
    ],
    'application' => [],
    'factory' => require __DIR__ . '/factory.php',
    'sockets' => require __DIR__ . '/esockets_debug.php',
    'multiThreading' => [
//        'disabled' => true,
    ]
];
