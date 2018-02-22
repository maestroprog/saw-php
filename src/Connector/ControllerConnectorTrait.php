<?php

namespace Maestroprog\Saw\Connector;

use Esockets\Client;

/**
 * @property Client $client
 */
trait ControllerConnectorTrait
{
    public function work(): \Generator
    {
        while (true) {
            $socket = $this->client->getConnectionResource()->getResource();
            $read = [$socket];
            $write = $except = [];
            // TODO decrease select timeout
            if (socket_select($read, $write, $except, 1)) {
                $this->client->read();
            }

            yield;
        }
    }
}
