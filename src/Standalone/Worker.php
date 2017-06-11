<?php

namespace Saw\Standalone;

use Esockets\Client;
use Saw\Application\ApplicationContainer;
use Saw\Command\AbstractCommand;
use Saw\Command\CommandHandler as EntityCommand;
use Saw\Command\ThreadKnow;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Esockets\debug\Log;
use Saw\Saw;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;

/**
 * Воркер, использующийся воркер-скриптом.
 * Используется для выполнения отдельных задач.
 * Работает в качестве демона в нескольких экземплярах.
 */
final class Worker
{
    public $work = true;
    private $client;
    private $applicationContainer;

    /**
     * @var CommandDispatcher
     */
    private $dispatcher;
    protected $core;

    public function __construct(Client $client, ApplicationContainer $applicationContainer)
    {
        if (!$client->isConnected()) {
            throw new PizcedException(); // todo
        }
        $this->client = $client;
        $this->applicationContainer = $applicationContainer;
        $this->client->onReceive($this->onRead());

        $this->client->onDisconnect(function () {
            Log::log('i disconnected!');
            $this->work = false;
        });
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
        $this->dispatcher = Saw::factory()->createDispatcher([
            new EntityCommand(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (AbstractCommand $context) {
                    $this->core->run();
                }
            ),
            new EntityCommand(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (AbstractCommand $context) {
                    $this->stop();
                }
            ),
            new EntityCommand(ThreadKnow::NAME, ThreadKnow::class),
            new EntityCommand(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    // выполняем задачу
                    $task = new Task($context->getRunId(), $context->getName(), $context->getFromDsc());
                    $this->core->runTask($task);
                }
            ),
            new EntityCommand(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    //todo
                    $this->core->receiveTask(
                        $context->getRunId(),
                        $context->getResult()
                    );
                }
            ),
        ]);
    }

    public function stop()
    {
        $this->work = false;
        $this->client->disconnect();
    }

    public function work()
    {
        $this->client->block();
        while ($this->work) {
            $this->client->read();

            if (count($this->core->getunQueue())) {
                /** @var Task $task */
                $task = array_shift($this->core->getRunQueue());
                $task->setResult($this->core->runCallback($task->getName()));
                $this->dispatcher->create(ThreadResult::NAME, $this->sc)
                    ->onError(function () {
                        //todo
                    })
                    ->run(ThreadResult::serializeTask($task));
            }
        }
    }

    public function run()
    {
        $this->applicationContainer->run();
    }

    protected function onRead(): callable
    {
        return function ($data) {
            Log::log('I RECEIVED  :)');
            Log::log(var_export($data, true));

            switch ($data) {
                case 'ACCEPT':
                    $this->dispatcher
                        ->create(WorkerAdd::NAME, $this->client)
                        ->onError(function () {
                            $this->stop();
                        })
                        ->onSuccess(function () {
                            $this->run();
                        })
                        ->run();
                    break;
                case 'INVALID':
                    // todo
                    throw new \RuntimeException('Is an invalid worker.');
                    break;
                case 'BYE':
                    $this->work = false;
                    break;
                default:
                    if (is_array($data) && $this->dispatcher->valid($data)) {
                        $this->dispatcher->dispatch($data, $this->client);
                    } else {
                        $this->client->send('INVALID');
                    }
            }
        };
    }
}
