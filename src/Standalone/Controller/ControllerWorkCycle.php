<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\Debug\Log;
use Esockets\Server;
use Maestroprog\Saw\Service\AsyncBus;

class ControllerWorkCycle implements CycleInterface
{
    private $server;

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

    public function work(): \Generator
    {
        while (true) {
            if ($this->dispatchSignals) {
                pcntl_signal_dispatch();
            }
            try {
                $this->server->find();
            } catch (\RuntimeException $e) {
                throw $e;
            }

            yield 'CYCLE' => AsyncBus::SIGNAL_PAUSE;
        }
    }
}
