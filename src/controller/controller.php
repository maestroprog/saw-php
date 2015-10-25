<?php
/**
 ** Start main controller for php
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:22
 */

function out($message)
{
    error_log($message);
}


require_once __DIR__ . '/../common/Net.php';
require_once __DIR__ . '/../common/Server.php';
require_once __DIR__ . '/../common/Peer.php';
require_once 'Saw.php';

use Saw\Saw;

define('SAW_ENVIRONMENT', 1+1);

$config = require '../config.php';
if (Saw::init($config)) {
    out('configured. start...');
    Saw::open() and Saw::start() or (out('Saw start failed') or exit);
    out('start end');
    out('work start');
    Saw::work();

}
register_shutdown_function(function () {
    Saw::stop();
    out('stopped');
});