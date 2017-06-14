<?php

namespace Saw\Standalone;

use Esockets\Client;
use Saw\Application\ApplicationContainer;
use Saw\Command\CommandHandler as EntityCommand;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Service\ApplicationLoader;
use Saw\Service\CommandDispatcher;
use Saw\Standalone\Controller\CycleInterface;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class WorkerCore implements CycleInterface
{
    private $client;
    private $applicationContainer;

    public function __construct(
        Client $peer,
        CommandDispatcher $commandDispatcher,
        ApplicationContainer $applicationContainer,
        ApplicationLoader $applicationLoader
    )
    {
        $this->client = $peer;
        $this->applicationContainer = $applicationContainer;
        $commandDispatcher->add([
            new EntityCommand(ThreadKnow::NAME, ThreadKnow::class),
            new EntityCommand(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    // выполняем задачу
                    $task = new Task($context->getRunId(), $context->getName(), $context->getFromDsc());
                    $this->runTask($task);
                }
            ),
            new EntityCommand(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    //todo
                    $this->receiveTask(
                        $context->getRunId(),
                        $context->getResult()
                    );
                }
            ),
        ]);
    }

    /**
     * Метод служит для запуска всех приложений внутри воркера.
     */
    public function run()
    {
        $this->applicationContainer->run();//todo
    }

    public function work()
    {
        if (count($this->getunQueue())) {
            /** @var Task $task */
            $task = array_shift($this->getRunQueue());
            $task->setResult($this->runCallback($task->getName()));
            $this->dispatcher->create(ThreadResult::NAME, $this->sc)
                ->onError(function () {
                    //todo
                })
                ->run(ThreadResult::serializeTask($task));
        }
    }


    /**
     * @var array
     */
    private $knowTasks = [];

    /**
     * Оповещает контроллер о том, что данный воркер узнал новую задачу.
     * Контроллер (и сам воркер) запоминает это.
     *
     * @param Task $task
     */
    public function addTask(Task $task)
    {
        if (!isset($this->knowTasks[$task->getName()])) {
            $this->knowTasks[$task->getName()] = 1;
        }
    }

    /**
     * @var Task[]
     */
    private $runQueue = [];

    /**
     * Постановка задачи в очередь на выполнение.
     *
     * @param Task $task
     */
    public function runTask(Task $task)
    {
        $this->runQueue[] = $task;
    }

    public function runCallback(string $name)
    {
        return $this->taskManager->runCallback($name);
    }

    public function & getRunQueue(): array
    {
        return $this->runQueue;
    }

    /**
     * Принимает от контроллера результат выполненной задачи.
     *
     * @param int $rid
     * @param $result
     */
    public function receiveTask(int $rid, &$result)
    {
        $task = $this->taskManager->getRunTask($rid);
        $task->setResult($result);
    }
}
