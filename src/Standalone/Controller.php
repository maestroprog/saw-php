<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\Client;
use Esockets\Debug\Log;
use Esockets\Server;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\PacketCommand;
use Maestroprog\Saw\Service\AsyncBus;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Standalone\Controller\ControllerWorkCycle;

/**
 * Класс демон-программы контроллера.
 */
final class Controller
{
    /**
     * Константы возможных типов подключающихся клиентов.
     */
    const CLIENT_INPUT = 1; // input-клиент, передающий запрос
    const CLIENT_WS_INPUT = 2; // WS input-клиент, передающий запрос (зарезервировано)
    const CLIENT_WORKER = 3; // воркер
    const CLIENT_CONTROLLER = 4; // контроллер. (зарезервировано)
    const CLIENT_DEBUG = 5; // отладчик

    private $workCycle;
    private $core;
    private $server;
    private $commandDispatcher;
    private $myPidFile;

    /**
     * @var bool
     */
    private $work = true;

    public function __construct(
        ControllerWorkCycle $workCycle,
        ControllerCore $core,
        Server $server,
        CommandDispatcher $commandDispatcher,
        string $myPidFile
    )
    {
        $this->workCycle = $workCycle;
        $this->core = $core;
        $this->server = $server;
        $this->myPidFile = $myPidFile;
        $this->commandDispatcher = $commandDispatcher;

        $this->commandDispatcher->addHandlers([
            new CommandHandler(PacketCommand::class, function (PacketCommand $context) {
                foreach ($context->getCommands() as $command) {
                    $this->commandDispatcher->dispatch($command, $context->getClient());
                }
            })
        ]);
    }

    /**
     * Старт контроллера.
     *
     * @throws \Exception
     */
    public function start()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function ($sig) {
                $this->stop();
            });
        }

        if (false === file_put_contents($this->myPidFile, getmypid())) {
            throw new \RuntimeException('Cannot save the pid in pid file.');
        }
        if (!$this->server->isConnected()) {
            throw new \Exception('Cannot start: not connected');
        }
        $this->server->onFound($this->onConnectPeer());
        register_shutdown_function(function () {
            $this->stop();
        });
//        $this->server->block();
        $this->work();
    }

    public function stop(): void
    {
        if ($this->work) {
            $this->work = false;
            $this->core->stop();
            unlink($this->myPidFile);
            $this->server->disconnect();
        }
    }

    private function onConnectPeer()
    {
        return function (Client $peer) {
            Log::log('peer connected ' . $peer->getPeerAddress());
            $peer->onReceive(function ($data) use ($peer) {
                if (!is_array($data) || !$this->commandDispatcher->valid($data)) {
                    $peer->send('INVALID');
                } else {
                    $this->commandDispatcher->dispatch($data, $peer);
                }
            });
            $peer->onDisconnect(function () use ($peer) {
                Log::log('peer disconnected');
            });
            if (!$peer->send('ACCEPT')) {
                $peer->disconnect(); // не нужен нам такой клиент
            }
        };
    }

    /**
     * Заставляем работать контроллер :)
     *
     * @throws \Exception
     */
    public function work()
    {
        $bus = new AsyncBus();
        $bus->attachGenerator($this->core->work());
        $bus->attachGenerator($this->workCycle->work());

        while ($this->work && $bus->valid()) {
            $bus->current();
            $bus->next();
        }
    }
}
