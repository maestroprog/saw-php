<?php

namespace Maestroprog\Saw;

use Maestroprog\Saw\Standalone\Controller;
use Maestroprog\Saw\Standalone\Debugger;
use Maestroprog\Saw\Standalone\Worker;

class SawInfrastructure extends Saw
{
    protected const CONTAINER_AUTOLOAD = true;

    public function controller(): Controller
    {
        return $this->getContainer()->get(Controller::class);
    }

    public function worker(): Worker
    {
        return $this->getContainer()->get(Worker::class);
    }

    public function debugger(): Debugger
    {
        return $this->getContainer()->get(Debugger::class);
    }
}
