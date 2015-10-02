<?php
/**
 ** Saw init script config file
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:52
 */
namespace Saw;

$config = [
    'net' => [
        'socket_domain' => AF_UNIX,
        'socket_address' => __DIR__ . DIRECTORY_SEPARATOR . 'saw-controller.sock',
    ],
    'params' => [
        'php_binary_path' => 'php'
    ]
];