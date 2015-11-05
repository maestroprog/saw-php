<?php
/**
 ** Start main controller for php
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:22
 */


require_once __DIR__ . '/../common/Net.php';
require_once __DIR__ . '/../common/Server.php';
require_once __DIR__ . '/../common/Peer.php';
require_once 'Saw.php';

use Saw\Saw;

define('SAW_ENVIRONMENT', 'Controller');

$config = require __DIR__ . '/../config.php';
if (Saw::init($config)) {
    out('configured. start...');
    Saw::open() and Saw::start() or (out('Saw start failed') or exit);
    out('start end');
    out('work start');
    Saw::work();
    out('work end');
}