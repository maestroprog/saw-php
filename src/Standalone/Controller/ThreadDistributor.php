<?php

namespace Saw\Standalone\Controller;

use Esockets\debug\Log;
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
    private $workerBalance;

    private $threadKnownIndex;
    private $threadRunQueue;
    private $threadRunned;

    public function __construct(
        CommandDispatcher $commandDispatcher,
        WorkerPool $workerPool,
        WorkerBalance $workerBalance
    )
    {
        $this->commandDispatcher = $commandDispatcher;
        $this->workerPool = $workerPool;
        $this->workerBalance = $workerBalance;

        $this->threadKnownIndex = new ControllerThreadPoolIndex();
        $this->threadRunQueue = new \SplQueue();
        $this->threadRunned = new ControllerThreadPoolIndex();

        $commandDispatcher->add([
            new CommandHandler(
                ThreadKnow::NAME,
                ThreadKnow::class,
                function (ThreadKnow $context) {
                    static $threadId = 0;
                    $thread = new ControlledThread(++$threadId, $context->getApplicationId(), $context->getUniqueId());
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
                    $thread = (new ControlledThread(++$runId, $context->getApplicationId(), $context->getUniqueId()))
                        ->setArguments($context->getArguments());
                    $this->threadRunQueue->push($thread);
                }
            ),
            new CommandHandler(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    $this->threadResult(
                        (int)$context->getPeer()->getConnectionResource()->getResource(),
                        $context->getRunId(),
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
        $filter = function (Worker $worker) {
            return $worker->getState() !== Worker::STOP;
        };
        while ($thread = $this->threadRunQueue->pop()) {
            /**
             * @var $thread ControlledThread
             */
            try {
                $worker = $this->workerBalance->getLowLoadedWorker($thread);
                /** @var $command ThreadRun */
                $this->commandDispatcher->create(ThreadRun::NAME, $worker->getClient())
                    ->onError(function () use ($thread) {
                        Log::log('error run task ' . $thread->getUniqueId());
                        //todo
                    })
                    ->onSuccess(function () use ($worker, $thread) {
                        //$this->taskRun[$rid] = $task;
                    })
                    ->run(ThreadRun::seriializeThread($thread));
                // т.к. выполнение задачи на стороне воркера произойдет раньше,
                // чем возврат ответа с успешным запуском
                // почистим массив, и запомним что поставили воркеру эту задачу
                $this->threadRunned->add($worker, $thread);
            } catch (\RuntimeException $e) {
//                $this->threadRunQueue->unshift($thread); // откладываем до лучших времён.
            } catch (\Throwable $e) {
                throw new \Exception('Cannot balance thread ' . $thread->getId(), 0, $e);
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
        $worker->addThreadToKnownList($thread);
    }

    public function threadResult(Worker $worker, int $runId, int $dsc, $result)
    {
        $this->threadRunned->getThread(); // todo
        $peer = $this->server->getPeerByDsc($dsc);
        // @todo empty name!
        $worker = $this->getWorkerByDsc($workerDsc);
        $task = $worker->getTask($runId);
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
