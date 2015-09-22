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

use Saw\Saw;

require_once 'config.php';
Saw::configure($config);
out('configured. init...');

Saw::socket_server() or Saw::start() or (out('Saw start failed') or exit);
out('init end');

Saw::socket_close();
out('closed');