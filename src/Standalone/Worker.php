<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\Base\Exception\ConnectionException;
use Esockets\Debug\Log;
use Maestroprog\Saw\Command\AbstractCommand;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\WorkerAdd;
use Maestroprog\Saw\Command\WorkerDelete;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Service\AsyncBus;
use Maestroprog\Saw\Service\Commander;

/**
 * Воркер, использующийся воркер-скриптом.
 * Используется для выполнения отдельных задач.
 * Работает в качестве демона в нескольких экземплярах.
 */
final class Worker
{
    private $core;
    private $connector;
    private $client;
    private $commandDispatcher;
    private $commander;

    private $work = true;

    /**
     * @var bool включить вызов pcntl_dispatch_signals()
     */
    private $dispatchSignals = false;

    private $bus;

    public function __construct(
        WorkerCore $core,
        ControllerConnectorInterface $connector,
        Commander $commander
    )
    {
        $this->core = $core;
        $this->connector = $connector;
        $this->client = $connector->getClient();
        $this->commandDispatcher = $connector->getCommandDispatcher();
        $this->commander = $commander;

        $this->commandDispatcher->addHandlers([
            new CommandHandler(WorkerDelete::class, function (AbstractCommand $context) {
                $this->stop();
            })
        ]);

        $this->bus = new AsyncBus();
        $this->bus->attachGenerator($this->core->work());
        $this->bus->attachGenerator($this->connector->work());
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function ($sig) {
                // пофиксил бесконечное ожидание завершение работы воркера
                static $tryCount = 0;
                $tryCount++;
                if ($tryCount > 3) {
                    $this->stop();
                }
            });
            $this->dispatchSignals = true;
        }

        $this->client->onReceive($this->onRead());
        $this->client->onDisconnect(function () {
            Log::log('i (worker) disconnected!!!');
            $this->work = false;
        });


        if (!$this->client->isConnected()) {
            try {
                $this->connector->connect();
            } catch (ConnectionException $e) {
                throw new \RuntimeException('Cannot start when not connected.', 0, $e);
            }
        }
        $this->work();
    }

    public function stop()
    {
        $this->work = false;
        $deleteCommand = (new WorkerDelete($this->client))
            ->onSuccess(function () {
                $this->stop();
            })
            ->onError(function () {
                throw new \RuntimeException('Cannot delete this worker from controller.');
            });
        $this->commander->runAsync($deleteCommand);
        $this->client->disconnect();
    }

    protected function onRead(): callable
    {
        return function ($data) {
            switch ($data) {
                case 'ACCEPT':
                    $addCommand = (new WorkerAdd($this->client, getmypid()))
                        ->onError(function () {
                            $this->stop();
                        })
                        ->onSuccess(function () {
                            $this->core->run();
                        });
                    $this->commander->runAsync($addCommand);
                    break;
                case 'INVALID':
                    throw new \RuntimeException('Is an invalid worker.');
                // no break
                case 'BYE':
                    $this->stop();
                    break;
                default:
                    if (is_array($data) && $this->commandDispatcher->valid($data)) {
                        $this->commandDispatcher->dispatch($data, $this->client);
                    } else {
                        $this->client->send('INVALID');
                    }
            }
        };
    }

    public function work(): void
    {
        while ($this->work && $this->bus->valid()) {
            if ($this->dispatchSignals) {
                pcntl_signal_dispatch();
            }
            $this->bus->current(); // не обязательно
            $this->bus->next();

            if (!$this->client->live()) {
                throw new \RuntimeException('Connection died!');
            }
        }
    }
}
