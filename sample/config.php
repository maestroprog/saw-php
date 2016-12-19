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
define('DS', DIRECTORY_SEPARATOR);

ini_set('display_errors', false);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');

require __DIR__ . '/../autoload.php';
require __DIR__ . '/../vendors/maestroprog/esockets/autoload.php';

\maestroprog\esockets\debug\Log::setEnv(SAW_ENVIRONMENT);

function out($message)
{
    $message = sprintf('{%s}: %s', SAW_ENVIRONMENT, $message);
    if (PHP_SAPI === 'cli') {
        fputs(STDOUT, $message);
    } else {
        if (ini_get('log_errors')) {
            error_log($message);
        } else {
            echo $message;
        }
    }
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

set_exception_handler(function (Throwable $e) {
    out(sprintf(
        "Вызвана ошибка %d: %s\r\nВ файле %s:%d\r\n%s\r\n\r\n",
        $e->getCode(),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        ''//print_r($e->getTrace(), true)
    ));
    sleep(20);
});

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    out(sprintf("[%s]: %s in %s at %d line %s\r\n", error_type($errno), $errstr, $errfile, $errline, ''/*, print_r($errcontext, true)*/));
});

return [
    'net' => [
        'socket_domain' => AF_INET,
        'socket_address' => '127.0.0.1',
        'socket_port' => 59090,
    ],
    'params' => [
        'php_binary_path' => PHP_OS === 'WINNT' ? 'd:\OpenServer\modules\php\PHP-7-x64\php.exe' : 'php',
        'controller_path' => __DIR__ . DS . 'workers' . DS . 'controller.php',
        'worker_path' => __DIR__ . DS . 'workers' . DS . 'worker.php',
        'worker_multiplier' => 4,
        'worker_max' => 1,
        'worker_app' => __DIR__ . DS . 'App.php',
        'worker_app_class' => App::class,
    ]
];
