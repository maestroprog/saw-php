<?php

namespace Saw\Standalone\Controller;

use Saw\Command\CommandHandler;
use Saw\Command\ThreadKnow;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Entity\Worker;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\ControlledThread;
use Saw\Thread\Pool\ControllerThreadPoolIndex;

/**
 * Распределитель потоков по воркерам.
 */
class ThreadDistributor implements CycleInterface
{
    private $commandDispatcher;
    private $workerPool;

    private $threadKnownIndex;
    private $threadRunQueue;

    public function __construct(CommandDispatcher $commandDispatcher, WorkerPool $workerPool)
    {
        $this->commandDispatcher = $commandDispatcher;
        $this->workerPool = $workerPool;

        $this->threadKnownIndex = new ControllerThreadPoolIndex();
        $this->threadRunQueue = new \SplQueue();

        $commandDispatcher->add([
            new CommandHandler(
                ThreadKnow::NAME,
                ThreadKnow::class,
                function (ThreadKnow $context) {
                    static $threadId = 0;
                    $thread = new ControlledThread(++$threadId, $context->getUniqueId());
                    $this->threadKnow(
                        $this->workerPool->getById((int)$context->getPeer()->getConnectionResource()->getResource()),
                        $thread
                    );
                }
            ),
            new CommandHandler(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    static $runId = 0;
                    $thread = (new ControlledThread(++$runId, $context->getUniqueId()))
                        ->setArguments($context->getArguments());
                    $this->threadRunQueue->add;
                    $this->tRun(
                        $context->getRunId(),
                        (int)$context->getPeer()->getConnectionResource()->getResource(),
                        $context->getName()
                    );
                }
            ),
            new CommandHandler(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    $this->tRes(
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
     * Предполагается, что этот метод будет запускать работу по перераспределению потоков по воркерам.
     */
    public function work()
    {
        foreach ($this->taskNew as $rid => $task) {
            if (!isset($this->tasksKnow[$task->getName()])) {
                continue;
            }
            $worker = $this->wMinT($task->getName(), function (Worker $worker) {
                return $worker->getState() !== Worker::STOP;
            });
            if ($worker >= 0) {
                $workerPeer = $this->server->getPeerByDsc($worker);
                try {
                    /** @var $command ThreadRun */
                    $this->commandDispatcher->create(ThreadRun::NAME, $workerPeer)
                        ->onError(function () use ($task) {
                            Log::log('error run task ' . $task->getName());
                            //todo
                        })
                        ->onSuccess(function () use ($worker, $rid, $task) {
                            //$this->taskRun[$rid] = $task;
                        })
                        ->run(ThreadRun::serializeTask($task));
                    // т.к. выполнение задачи на стороне воркера произойдет раньше,
                    // чем возврат ответа с успешным запуском
                    // почистим массив, и запомним что поставили воркеру эту задачу
                    $this->workers[$worker]->addTask($task);
                    unset($this->taskNew[$rid]);
                } catch (\Throwable $e) {
                    throw new \Exception('Cannot balanced Task ' . $task->getRunId(), 0, $e);
                }
            }
        }
    }

    /**
     * Метод, который будет вызван,
     * когда воркер сообщит о новом потоке, который он только что узнал.
     *
     * @param Worker $worker
     * @param AbstractThread $thread
     */
    public function threadKnow(Worker $worker, AbstractThread $thread)
    {
        $this->threadKnownIndex->add($worker, $thread);
        $worker->addThreadToKnownList()
    }

    /**
     * Функция добавляет задачу в очередь на выполнение для заданного воркера.
     *
     * @param int $runId
     * @param int $dsc
     * @param string $name
     */
    public function tRun(int $runId, int $dsc, string $name)
    {
        static $rid = 0; // task run ID
        $this->taskNew[$rid] = new Task($runId, $name, $dsc);
        $rid++;
    }

    public function tRes(int $rid, int $workerDsc, int $dsc, &$result)
    {
        $peer = $this->server->getPeerByDsc($dsc);
        // @todo empty name!
        $worker = $this->getWorkerByDsc($workerDsc);
        $task = $worker->getTask($rid);
        $task->setResult($result);
        $worker->removeTask($task); // release worker
        $this->commandDispatcher->create(ThreadResult::NAME, $peer)
            ->onError(function () {
                //todo
            })
            ->onSuccess(function () {
                //todo
            })
            ->run(ThreadResult::serializeTask($task));
        Log::log('I send res to ' . $peer->getDsc());
    }
}
