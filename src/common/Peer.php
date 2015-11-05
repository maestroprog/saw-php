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
class Peer extends Net
{
    /**
     * @var bool connection state
     */
    private $connected = false;

    /* event variables */

    /**
     * @var callable
     */
    private $event_disconnect;

    public function __construct(&$connection)
    {
        if (is_resource($connection)) {
            $this->connection = $connection;
            $this->connected = true;
            return $this;
        }
        return false;
    }

    public function close()
    {
        parent::close();
        $this->connected = false;
    }

    public function doDisconnect()
    {
        $this->close();
    }

    public function onDisconnect(callable $callback)
    {
        $this->event_disconnect = $callback;
    }

    protected function _onDisconnect()
    {
        if (is_callable($this->event_disconnect)) {
            call_user_func($this->event_disconnect);
        }
    }

}