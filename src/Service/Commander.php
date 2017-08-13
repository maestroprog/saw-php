<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Connector\ControllerConnectorInterface;

final class Commander
{
    private $connector;

    public function __construct(ControllerConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function run()
    {

    }

    public function runAsync()
    {
        $this->connector->work();
    }
}
