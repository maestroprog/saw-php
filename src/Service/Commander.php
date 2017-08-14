<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Command\ContainerOfCommands;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;

final class Commander
{
    private $connector;
    private $commands;

    public function __construct(ControllerConnectorInterface $connector, ContainerOfCommands $commands)
    {
        $this->connector = $connector;
        $this->commands = $commands;
    }

    public function run()
    {

    }

    public function runAsync()
    {
        $this->connector->work();
    }
}
