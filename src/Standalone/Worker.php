<?php

namespace Saw\Standalone;

use Esockets\Client;
use Esockets\debug\Log;
use Saw\Command\AbstractCommand;
use Saw\Command\CommandHandler;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Service\CommandDispatcher;

/**
 * Воркер, использующийся воркер-скриптом.
 * Используется для выполнения отдельных задач.
 * Работает в качестве демона в нескольких экземплярах.
 */
final class Worker
{
    private $core;
    private $client;
    private $commandDispatcher;

    private $work = true;

    /**
     * @var bool включить вызов pcntl_dispatch_signals()
     */
    private $dispatchSignals = false;

    public function __construct(
        WorkerCore $core,
        Client $client,
        CommandDispatcher $commandDispatcher
    )
    {
        $this->core = $core;
        $this->client = $client;
        $this->commandDispatcher = $commandDispatcher;
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function ($sig) {
                $this->commandDispatcher
                    ->create(WorkerDelete::NAME, $this->client)
                    ->onSuccess(function () {
                        $this->stop();
                    })
                    ->onError(function () {
                        throw new \RuntimeException('Cannot delete this worker from controller.');
                    })
                    ->run();
            });
            $this->dispatchSignals = true;
        }

        if (!$this->client->isConnected()) {
            throw new \RuntimeException('Cannot start when not connected.');
        }
        $this->client->onReceive($this->onRead());
        $this->client->onDisconnect(function () {
            Log::log('i disconnected!');
            $this->work = false;
        });

        $this->commandDispatcher->add([
            new CommandHandler(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (AbstractCommand $context) {
                    $this->core->run(); // запуск приложений
                }
            ),
            new CommandHandler(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (AbstractCommand $context) {
                    $this->stop();
                }
            )
        ]);
        $this->work();
    }

    public function stop()
    {
        $this->work = false;
        $this->client->disconnect();
    }

    public function work()
    {
//        $this->client->block(); todo check
        while ($this->work) {
            if ($this->dispatchSignals) {
                pcntl_signal_dispatch();
            }
            $this->core->work();
            $this->client->read();
        }
    }

    protected function onRead(): callable
    {
        return function ($data) {
            Log::log('I RECEIVED  :)');
            Log::log(var_export($data, true));

            switch ($data) {
                case 'ACCEPT':
                    $this->commandDispatcher
                        ->create(WorkerAdd::NAME, $this->client)
                        ->onError(function () {
                            $this->stop();
                        })
                        ->onSuccess(function () {
                            $this->core->run();
                        })
                        ->run(['pid' => getmypid()]);
                    break;
                case 'INVALID':
                    throw new \RuntimeException('Is an invalid worker.');
                    break;
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
}
