<?php
/**
 ** Start main controller for php
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:22
 */


ini_set('display_errors', false);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');


function out($message)
{
    error_log($message);
}

require_once 'config.php';
require_once __DIR__ . '/../common/Net.php';
require_once 'Saw.php';

use Saw\Saw;

Saw::init($config);
out('configured. input...');

Saw::socket_server() and Saw::start() or (out('Saw start failed') or exit);
out('input end');

register_shutdown_function(function () {
    Saw::socket_close();
    out('closed');
});