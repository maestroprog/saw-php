<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\debug\Log;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadBroadcast;
use Maestroprog\Saw\Command\ThreadKnow;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Entity\Worker;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\ControlledBroadcastThread;
use Maestroprog\Saw\Thread\ControlledThread;
use Maestroprog\Saw\Thread\Pool\ControllerThreadPoolIndex;
use Maestroprog\Saw\Thread\Pool\PoolOfUniqueThreads;
use Maestroprog\Saw\Thread\Pool\ThreadLinker;
use Maestroprog\Saw\Thread\StubThread;

/**
 * Распределитель потоков по воркерам.
 */
final class ThreadDistributor implements CycleInterface
{
    private $commander;
    private $workerPool;
    private $workerBalance;

    /**
     * Индекс известных потоков.
     *
     * @var PoolOfUniqueThreads
     */
    private $threadKnownIndex;

    /**
     * Очередь потоков на выполнение.
     *
     * @var \SplStack
     */
    private $threadRunQueue;

    /**
     * Список потоков, взятых на выполнение из очереди.
     *
     * @var ControllerThreadPoolIndex
     */
    private $threadRunSources;

    /**
     * Список работающих потоков.
     *
     * @var ControllerThreadPoolIndex
     */
    private $threadRunWork;

    /**
     * Связывальющик потоков.
     *
     * @var ThreadLinker
     */
    private $threadLinks;

    public function __construct(
        CommandDispatcher $commandDispatcher,
        Commander $commander,
        WorkerPool $workerPool,
        WorkerBalance $workerBalance
    )
    {
        $this->commander = $commander;
        $this->workerPool = $workerPool;
        $this->workerBalance = $workerBalance;

        // иднекс известных потоков
        $this->threadKnownIndex = new PoolOfUniqueThreads(); // todo why not used?
        // очередь потоков на выполнение
        $this->threadRunQueue = new \SplStack();
        // индекс потоков, поставленных на выполнение из очереди
        $this->threadRunSources = new ControllerThreadPoolIndex();
        // индекс работающих потоков
        $this->threadRunWork = new ControllerThreadPoolIndex();
        // связывальщик потоков
        $this->threadLinks = new ThreadLinker();

        $commandDispatcher->addHandlers([
            new CommandHandler(ThreadKnow::class, function (ThreadKnow $context) {
                static $threadId = 0;
                $thread = new StubThread(++$threadId, $context->getApplicationId(), $context->getUniqueId());
                // добавление потока в список известных
                $this->threadKnow(
                    $this->workerPool->getById($context->getClient()->getConnectionResource()->getId()),
                    $thread
                );
            }),
            new CommandHandler(ThreadRun::class, function (ThreadRun $context) {
                $thread = (new ControlledThread(
                    $context->getRunId(),
                    $context->getApplicationId(),
                    $context->getUniqueId(),
                    $context->getClient()
                ))->setArguments($context->getArguments());

                // добавление потока в очередь выполнения
                $this->threadRunQueue->push($thread);
            }),
            new CommandHandler(ThreadBroadcast::class, function (ThreadBroadcast $context) {
                $thread = (new BroadcastThread(
                    $context->getRunId(),
                    $context->getApplicationId(),
                    $context->getUniqueId(),
                    $context->getClient()
                ))->setArguments($context->getArguments());

                // добавление потока в очередь выполнения
                $this->threadRunQueue->push($thread);
            }),
            new CommandHandler(ThreadResult::class, function (ThreadResult $context) {
                // получение и обработка результата выполнения потока
                $this->threadResult(
                    $this->workerPool->getById($context->getClient()->getConnectionResource()->getId()),
                    $context->getRunId(),
                    $context->getResult()
                );
            }),
        ]);
    }

    /**
     * Перераспределяет потоки по воркерам.
     * @return void
     * @throws \Exception
     */
    public function work()
    {
        while (!$this->threadRunQueue->isEmpty()) {
            /** @var $thread ControlledThread */
            $thread = $this->threadRunQueue->shift();
            try {
                if ($thread instanceof BroadcastThread) {
                    $this->threadBroadcast($thread);
                } else {
                    $worker = $this->workerBalance->getLowLoadedWorker($thread);
                    $this->threadRun($worker, $thread);
                }
            } catch (\RuntimeException $e) {
                $this->threadRunQueue->push($thread); // откладываем до лучших времён.
            } catch (\Throwable $e) {
                throw new \Exception('Cannot balance thread ' . $thread->getUniqueId(), 0, $e);
            }
        }
    }

    /**
     * Метод, который будет вызван,
     * когда воркер сообщит о новом потоке, который он только что узнал.
     *
     * @param Worker $worker
     * @param AbstractThread $thread
     * @return void
     */
    public function threadKnow(Worker $worker, AbstractThread $thread)
    {
        $this->threadKnownIndex->add($thread);
        $worker->addThreadToKnownList($thread);
    }

    /**
     * Выполняет постановку задачи на выполнение потока указанному воркеру.
     *
     * @param Worker $worker Воркер, которому предстоит передать поток на выполнение
     * @param AbstractThread $sourceThread Исходный поток, который будет передан на выполнение
     * @return void
     */
    public function threadRun(Worker $worker, AbstractThread $sourceThread)
    {
        static $runId = 0;

        $runThread = (new ControlledThread(
            ++$runId,
            $sourceThread->getApplicationId(),
            $sourceThread->getUniqueId(),
            $worker->getClient()
        ))->setArguments($sourceThread->getArguments());

        // todo start check
        // сообщаем сущности воркера, что он выполняет поток
        $worker->addThreadToRunList($runThread);
        $this->threadRunSources->add($worker, $sourceThread);
        $this->threadRunWork->add($worker, $runThread);
        // связываем поток для выполнения с исходным потоком
        $this->threadLinks->linkThreads($runThread, $sourceThread);
        // todo check ---

        $thread = (new ThreadRun(
            $worker->getClient(),
            $runThread->getId(),
            $runThread->getApplicationId(),
            $runThread->getUniqueId(),
            $runThread->getArguments()
        ))
            ->onError(function () use ($sourceThread) {
                Log::log('Error run task ' . $sourceThread->getUniqueId());
                throw new \RuntimeException('Error run thread.');
            })
            ->onSuccess(function () use ($worker, $sourceThread, $runThread) {

            });

        $this->commander->runAsync($thread);
    }

    /**
     * Выполняет постановку задачи на выполнение потока указанному воркеру.
     *
     * @param BroadcastThread $sourceThread Исходный поток, который будет передан на выполнение
     * @return void
     */
    public function threadBroadcast(BroadcastThread $sourceThread)
    {
        /** @var Worker $worker */
        foreach ($this->workerPool as $worker) {
            $this->threadRun($worker, $sourceThread);
            // todo сделать оповещение об успешном выполнении потока
        }
    }

    /**
     * Выполняет обработку результата выполнения потока,
     * и перенаправляет его к исходному постановщику задачи.
     *
     * @param Worker $worker От кого пришёл результат
     * @param int $runId По какому потоку пришёл результат
     * @param mixed $result Результат выполнения потока
     * @return void
     */
    public function threadResult(Worker $worker, int $runId, $result)
    {
        $runThread = $this->threadRunWork->getThreadById($runId);
        $sourceThread = $this->threadLinks->getLinkedThread($runThread);
        // отвязываем поток для выполнения от исходного потока
        $this->threadLinks->unlinkThreads($runThread);

        $sourceThread->setResult($result);
        // удаляем из сущности воркера информацию о завершённом потоке
        $worker->removeRunThread($runThread);

        $this->threadRunSources->removeThread($sourceThread);
        $this->threadRunWork->removeThread($runThread);

        if (!$sourceThread instanceof ControlledThread) {
            throw new \LogicException('Unknown thread object!');
        }

        if ($sourceThread instanceof BroadcastThread) {
            /**
             * Широковещательные потоки на данный момент
             * не поддерживают сбор и отправку результатов.
             */
            return;
        }

        $this->commander->runAsync(
            (new ThreadResult(
                $sourceThread->getThreadFrom(),
                $sourceThread->getId(),
                $sourceThread->getApplicationId(),
                $sourceThread->getUniqueId(),
                $sourceThread->getResult()
            ))
                ->onError(function () {

                })
                ->onSuccess(function () use ($sourceThread, $runThread) {

                })
        );
    }

    public function getWorkerPool(): WorkerPool
    {
        return $this->workerPool;
    }

    public function getWorkerBalance(): WorkerBalance
    {
        return $this->workerBalance;
    }

    public function getThreadKnownIndex(): PoolOfUniqueThreads
    {
        return $this->threadKnownIndex;
    }

    public function getThreadRunQueue(): \SplStack
    {
        return $this->threadRunQueue;
    }

    public function getThreadRunSources(): ControllerThreadPoolIndex
    {
        return $this->threadRunSources;
    }

    public function getThreadRunWork(): ControllerThreadPoolIndex
    {
        return $this->threadRunWork;
    }

    public function getThreadLinks(): ThreadLinker
    {
        return $this->threadLinks;
    }
}
