<?php
/**
 ** Saw input script config file
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:52
 */

ini_set('display_errors', false);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');
file_put_contents(__DIR__ . '/messages.log', ''); // очищаем лог-файл

return [
    'net' => [
        'socket_domain' => AF_UNIX,
        'socket_address' => __DIR__ . DIRECTORY_SEPARATOR . 'saw-controller.sock',
    ],
    'params' => [
        'php_binary_path' => 'php'
    ]
];