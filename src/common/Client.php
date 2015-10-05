<?php
/**
 * Net Client code snippet
 *
 * Created by PhpStorm.
 * User: yarullin
 * Date: 02.10.2015
 * Time: 8:55
 */

namespace Saw\Net;

/**
 * Class Client
 * @package Saw\Net
 */
class Client extends Net
{
    /* event variables */

    /**
     * @var callable
     */
    private $event_disconnect;

    abstract public function connect();

    abstract public function doDisconnect();

    abstract public function onDisconnect(callable $callback);
}