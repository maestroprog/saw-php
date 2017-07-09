<?php

namespace Saw\Standalone;

use Esockets\debug\Log;
use Saw\Connector\ControllerConnectorInterface;

class Debugger
{
    private $connector;

    public function __construct(
        ControllerConnectorInterface $connector
    )
    {
        $this->connector = $connector;
    }

    public function start()
    {
        $this->connector->getClient()->onReceive(function ($data) {

            if (is_array($data) && $this->connector->getCommandDispatcher()->valid($data)) {
                $this->connector->getCommandDispatcher()->dispatch($data, $this->connector->getClient());
            } else {
                Log::log('Invalid data', $data);
            }
        });
        $this->connector->getClient()->onDisconnect(function () {
            Log::log('i disconnected!');
            exit;
        });

        $this->connector->connect();

        if (!$this->connector->getClient()->isConnected()) {
            throw new \RuntimeException('Cannot start when not connected.');
        }

//        $this->connector->getClient()->block();
        $pcntl = false;
        if (extension_loaded('pcntl')) {
            $pcntl = true;
            pcntl_signal(SIGINT, function (int $signal) {
                exit;
            });
        }
        while (true) {
            if ($pcntl) {
                pcntl_signal_dispatch();
            }
            $this->connector->work();
            $cmd = fgets(STDIN);
            var_dump($cmd);
        }
    }
}
