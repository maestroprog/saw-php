<?php
/**
 ** Saw common config file
 * Общий конфиг-файл настроек фреймворка.
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:52
 */
define('INTERVAL', 1000); // 1ms

return [
    'net' => [
        'socket_domain' => AF_INET,
        'socket_address' => '127.0.0.1',
        'socket_port' => 59090,
    ],
    'executor' => [
        'php_binary_path' => PHP_OS === 'WINNT' ? 'd:\OpenServer\modules\php\PHP-7.0\php.exe' : 'php',
        'controller_path' => __DIR__ . '/../../daemon/controller.php',
        'worker_path' => __DIR__ . '/../../daemon/worker.php',
    ],
    'controller' => [
        'worker_multiplier' => 4,
        'worker_max' => 8,
        'worker_app' => __DIR__ . DS . 'App.php',
        'worker_app_class' => App::class,
    ]
];
