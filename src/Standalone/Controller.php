<?php

namespace Saw\Standalone;

use Esockets\Client;
use Esockets\debug\Log;
use Esockets\Server;
use Saw\Command\AbstractCommand;
use Saw\Command\CommandHandler as EntityCommand;
use Saw\Command\ThreadKnow;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Service\CommandDispatcher;

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

    /**
     * @var bool
     */
    public $work = true;

    /**
     * @var bool включить вызов pcntl_dispatch_signals()
     */
    private $dispatchSignals = false;

    /**
     * @var int множитель задач
     */
    public $workerMultiplier = 1;

    /**
     * @var int количество инстансов
     */
    public $workerMax = 1;

    private $core;
    private $server;

    private $commandDispatcher;
    private $myPidFile;

    public function __construct(
        ControllerCore $core,
        Server $server,
        CommandDispatcher $commandDispatcher,
        string $myPidFile
    )
    {
        $this->core = $core;
        $this->server = $server;
        $this->myPidFile = $myPidFile;
        $this->commandDispatcher = $commandDispatcher->add([
            new EntityCommand(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (AbstractCommand $context) {
                    return $this->core->wAdd((int)$context->getPeer()->getConnectionResource()->getResource());
                }
            ),
            new EntityCommand(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (AbstractCommand $context) {
                    $this->core->wDel((int)$context->getPeer()->getConnectionResource()->getResource());
                }
            ),
            new EntityCommand(
                ThreadKnow::NAME,
                ThreadKnow::class,
                function (AbstractCommand $context) {
                    $this->core->tAdd((int)$context->getPeer()->getConnectionResource()->getResource(), $context->getData()['name']);
                }
            ),
            new EntityCommand(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    $this->core->tRun($context->getRunId(), (int)$context->getPeer()->getConnectionResource()->getResource(), $context->getName());
                }
            ),
            new EntityCommand(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    $this->core->tRes(
                        $context->getRunId(),
                        (int)$context->getPeer()->getConnectionResource()->getResource(),
                        $context->getFromDsc(),
                        $context->getResult()
                    );
                }
            ),
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
            $this->dispatchSignals = true;
        }

        if (false === file_put_contents($this->myPidFile, getmypid())) {
            throw new \RuntimeException('Cannot save the pid in pid file.');
        }
        if (!$this->server->isConnected()) {
            throw new \Exception('Cannot start: not connected');
        }
        $this->server->onFound($this->onConnectPeer());
        /*todo register_shutdown_function(function () {
            $this->stop();
        });*/
        $this->work();
    }

    /**
     * Заставляем работать контроллер :)
     */
    public function work()
    {
        while ($this->work) {
            /*$this->core->wBalance(); // балансируем воркеры
            $this->core->tBalance(); // раскидываем задачки
            if ($this->dispatchSignals) {
                pcntl_signal_dispatch();
            }*/
            $this->server->find();
        }
    }

    public function stop()
    {
        $this->work = false;
        $this->server->disconnect();
    }

    private function onConnectPeer()
    {
        return function (Client $peer) {
            $peer->unblock();
            Log::log('peer connected ' . $peer->getPeerAddress());
            $peer->onReceive(function ($data) use ($peer) {
                Log::log('I RECEIVED  :) from ' . $peer->getConnectionResource()->getResource() . $peer->getPeerAddress());
                Log::log(var_export($data, true));
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
                Log::log('HELLO FAIL SEND!');
                $peer->disconnect(); // не нужен нам такой клиент
            }
        };
    }
}
