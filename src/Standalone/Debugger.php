<?php

namespace Saw\Standalone;

use Esockets\debug\Log;
use Saw\Command\CommandHandler;
use Saw\Command\DebugCommand;
use Saw\Command\DebugData;
use Saw\Connector\ControllerConnectorInterface;

class Debugger
{
    private $connector;

    public function __construct(
        ControllerConnectorInterface $connector
    )
    {
        $this->connector = $connector;
        $connector->getCommandDispatcher()->add([
            new CommandHandler(DebugCommand::NAME, DebugCommand::class),
            new CommandHandler(DebugData::NAME, DebugData::class, function (DebugData $context) {
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
