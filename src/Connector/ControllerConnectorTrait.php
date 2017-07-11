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
        $this->client->read();
    }
}
