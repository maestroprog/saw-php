<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\Server;
use Maestroprog\Saw\Standalone\ControllerCore;

class ControllerWorkCycle implements CycleInterface
{
    private $server;
    private $core;

    /**
     * @var bool включить вызов pcntl_dispatch_signals()
     */
    private $dispatchSignals = false;

    public function __construct(Server $server)
    {
        $this->server = $server;
        if (extension_loaded('pcntl')) {
            $this->dispatchSignals = true;
        }
    }

    public function work()
    {
        if ($this->dispatchSignals) {
            pcntl_signal_dispatch();
        }
        try {
            $this->server->find();
        } catch (\RuntimeException $e) {
            ; // todo
            throw $e;
        }
    }
}
