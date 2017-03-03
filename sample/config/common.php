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
use maestroprog\saw\Application\Basic;

define('INTERVAL', 1000); // 1ms

return [
    'executor' => [
        'php_binary_path' => PHP_OS === 'WINNT' ? 'С:\OpenServer\modules\php\PHP-7.0\php.exe' : 'php',
        'controller_path' => __DIR__ . '/../daemon/controller.php',
        'mediator_path' => __DIR__ . '/../daemon/mediator.php',
        'worker_path' => __DIR__ . '/../daemon/worker.php',
    ],
    'controller' => [
        'net' => [ // секция сетевых настроек контроллера
            'socket_address' => '0.0.0.0', // сетевой адрес контроллера
            'socket_port' => 59090, // сетевой порт контроллера
            'socket_domain' => AF_INET,

            'external_socket_address' => '192.168.1.66', // внешний адрес, нужен при создании кластера
        ],
        'worker_multiplier' => 4,
        'worker_max' => 8,
    ],
    'mediator' => [
        'enabled' => true, // включить поддержку посредника
        'auto_run' => true,
    ],
    'application' => [
        'saw.sample.www' => [
            'class' => Basic::class,
            'run_script' => __DIR__ . '/../app.php',
            'end_script' => __DIR__ . '/../end.php',
        ],
    ],
];