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


$config = require __DIR__ . '/../config.php';
$controller = \maestroprog\Saw\Factory::getInstance()->configure($config)->createController();

out('work start');
$controller->work();
out('work end');
