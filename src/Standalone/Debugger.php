<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\base\exception\ConnectionException;
use Esockets\debug\Log;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\DebugCommand;
use Maestroprog\Saw\Command\DebugData;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;

class Debugger
{
    private $connector;

    public function __construct(
        ControllerConnectorInterface $connector
    )
    {
        $this->connector = $connector;
        $connector->getCommandDispatcher()->addHandlers([
            new CommandHandler(DebugCommand::class),
            new CommandHandler(DebugData::class, function (DebugData $context) {
                $this->output($context);
                return true;
            }),
        ]);
    }

    public function start()
    {
        $this->connector->getClient()->onReceive(function ($data) {
            if ($data === 'ACCEPT') {
                return;
            }
            if (is_array($data) && $this->connector->getCommandDispatcher()->valid($data)) {
                $this->connector->getCommandDispatcher()
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

//        $this->connector->getClient()->block();
        $pcntl = false;
        if (extension_loaded('pcntl')) {
            $pcntl = true;
            pcntl_signal(SIGINT, function (int $signal) {
                exit;
            });
        }
        echo 'Please, type command', PHP_EOL;
        stream_set_blocking(STDIN, 0);
        while (true) {
            if ($pcntl) {
                pcntl_signal_dispatch();
            }
            $this->connector->work();
            if ($cmd = fgets(STDIN)) {
                $this->connector->getCommandDispatcher()
                    ->create(DebugCommand::NAME, $this->connector->getClient())
                    ->onSuccess(function () {

                    })
                    ->onError(function () use ($cmd) {
                        throw new \RuntimeException('Cannot exec cmd: ' . $cmd);
                    })
                    ->run(['query' => trim($cmd)]);
            }
            usleep(100000);
        }
    }

    protected function output(DebugData $debug)
    {
        switch ($debug->getType()) {
            default:
                fwrite(STDOUT, (string)$debug->getResult() . PHP_EOL);
        }
    }
}
