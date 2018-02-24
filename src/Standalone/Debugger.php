<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\Base\Exception\ConnectionException;
use Esockets\Debug\Log;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\DebugCommand;
use Maestroprog\Saw\Command\DebugData;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Service\Commander;

class Debugger
{
    private $connector;
    private $commander;

    public function __construct(
        ControllerConnectorInterface $connector,
        Commander $commander
    )
    {
        $this->connector = $connector;
        $this->commander = $commander;
        $connector->getCommandDispatcher()->addHandlers([
            new CommandHandler(DebugData::class, function (DebugData $context) {
                $this->output($context);
            }),
        ]);
    }

    protected function output(DebugData $debug)
    {
        switch ($debug->getType()) {
            default:
                fwrite(STDOUT, (string)$debug->getResult() . PHP_EOL);
        }
    }

    public function start()
    {
        $this->connector->getClient()->onReceive(function ($data) {
            if ($data === 'ACCEPT') {
                return;
            }
            if (is_array($data) && $this->connector->getCommandDispatcher()->valid($data)) {
                $this
                    ->connector
                    ->getCommandDispatcher()
                    ->dispatch($data, $this->connector->getClient());
            } else {
                Log::log('Invalid data', $data);
            }
        });
        $this->connector->getClient()->onDisconnect(function () {
            Log::log('i disconnected!');
            exit;
        });

        if (!$this->connector->getClient()->isConnected()) {
            try {
                $this->connector->connect();
            } catch (ConnectionException $e) {
                throw new \RuntimeException('Cannot start when not connected.', 0, $e);
            }
        }

        $pcntl = false;
        if (extension_loaded('pcntl')) {
            $pcntl = true;
            pcntl_signal(SIGINT, function (int $signal) {
                exit;
            });
        }
        echo 'Please, type command', PHP_EOL;
        stream_set_blocking(STDIN, 0);
        $workGenerator = $this->connector->work();
        while (true) {
            if ($pcntl) {
                pcntl_signal_dispatch();
            }
            $workGenerator->current();
            $workGenerator->next();
            if ($cmd = fgets(STDIN)) {
                $this->commander->runAsync(
                    (new DebugCommand($this->connector->getClient(), trim($cmd)))
                        ->onError(function () use ($cmd) {
                            throw new \RuntimeException('Cannot exec cmd: ' . $cmd);
                        })
                );
            }
            usleep(100000);
        }
    }
}
