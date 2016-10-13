<?php
/**
 ** Start main controller for php
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:22
 */

use maestroprog\Saw\Controller;

define('SAW_ENVIRONMENT', 'Controller');

$config = require __DIR__ . '/../config.php';
if (Controller::init($config)) {
    out('configured. start...');
    Controller::open() and Controller::start() or (out('Saw start failed') or exit);
    out('start end');
    out('work start');
    Controller::work();
    out('work end');
}