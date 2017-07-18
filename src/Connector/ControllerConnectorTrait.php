<?php

namespace Saw\Connector;

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
        if (socket_select($read, $write, $except, 1)) {
            $this->client->read();
        }
    }
}
