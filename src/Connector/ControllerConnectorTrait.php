<?php

namespace Maestroprog\Saw\Connector;

use Esockets\Client;

trait ControllerConnectorTrait
{
    /**
     * @var Client
     */
    private $client;

    public function work()
    {
        $socket = $this->client->getConnectionResource()->getResource();
        $read = [$socket];
        $write = $except = [];
        // TODO decrease select timeout
        if (socket_select($read, $write, $except, 1)) {
            $this->client->read();
        }
        /* TODO REFACTOR OR ADD: */
        /*
        if (extension_loaded('pcntl')) {
            pcntl_signal_dispatch();
        }*/
    }
}
