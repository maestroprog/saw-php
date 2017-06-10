<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');

use Saw\Saw;

require_once '../../src/bootstrap.php';
require_once 'MyApplication.php';

Saw::instance()
    ->init(require __DIR__ . '/../config/saw.php')
    ->instanceApp(MyApplication::class)
    ->run();
