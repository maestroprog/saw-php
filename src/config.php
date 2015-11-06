<?php
/**
 ** Saw common config file
 * Общий конфиг-файл, для простоты настройки (все настройки в одном файле)
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:52
 */
defined('SAW_ENVIRONMENT') or define('SAW_ENVIRONMENT', 'Unknown');
define('INTERVAL', 1000); // 1ms

ini_set('display_errors', false);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');

function out($message)
{
    $message = sprintf('{%s}: %s', SAW_ENVIRONMENT, $message);
    if (ini_get('log_errors'))
        error_log($message);
    else
        echo $message;
}

function error_type($type)
{
    switch ($type) {
        case E_ERROR: // 1 //
            return 'E_ERROR';
        case E_WARNING: // 2 //
            return 'E_WARNING';
        case E_PARSE: // 4 //
            return 'E_PARSE';
        case E_NOTICE: // 8 //
            return 'E_NOTICE';
        case E_CORE_ERROR: // 16 //
            return 'E_CORE_ERROR';
        case E_CORE_WARNING: // 32 //
            return 'E_CORE_WARNING';
        case E_COMPILE_ERROR: // 64 //
            return 'E_COMPILE_ERROR';
        case E_COMPILE_WARNING: // 128 //
            return 'E_COMPILE_WARNING';
        case E_USER_ERROR: // 256 //
            return 'E_USER_ERROR';
        case E_USER_WARNING: // 512 //
            return 'E_USER_WARNING';
        case E_USER_NOTICE: // 1024 //
            return 'E_USER_NOTICE';
        case E_STRICT: // 2048 //
            return 'E_STRICT';
        case E_RECOVERABLE_ERROR: // 4096 //
            return 'E_RECOVERABLE_ERROR';
        case E_DEPRECATED: // 8192 //
            return 'E_DEPRECATED';
        case E_USER_DEPRECATED: // 16384 //
            return 'E_USER_DEPRECATED';
    }
    return "";
}

set_exception_handler(function (Exception $e) {
    out(sprintf('Вызвана ошибка %d: %s; %s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
});

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    out(sprintf('[%s]: %s in %s at %d line', error_type($errno), $errstr, $errfile, $errline));
});

return [
    'net' => [
        'socket_domain' => AF_UNIX,
        'socket_address' => __DIR__ . '/controller/saw-controller.sock',
    ],
    'params' => [
        'php_binary_path' => 'php',
        'controller_path' => __DIR__ . '/controller',
    ]
];