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
use Saw\Thread\Pool\PoolOfUniqueThreads;
use Saw\Thread\Pool\ThreadLinker;
use Saw\Thread\StubThread;

/**
 * Распределитель потоков по воркерам.
 */
class ThreadDistributor implements CycleInterface
{
    private $commandDispatcher;
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
        WorkerPool $workerPool,
        WorkerBalance $workerBalance
    )
    {
        $this->commandDispatcher = $commandDispatcher;
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

        $commandDispatcher->add([
            new CommandHandler(
                ThreadKnow::NAME,
                ThreadKnow::class,
                function (ThreadKnow $context) {
                    static $threadId = 0;
                    $thread = new StubThread(++$threadId, $context->getApplicationId(), $context->getUniqueId());
                    // добавление потока в список известных
                    $this->threadKnow(
                        $this->workerPool->getById($context->getPeer()->getConnectionResource()->getId()),
                        $thread
                    );
                }
            ),
            new CommandHandler(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {

                    $thread = (new ControlledThread(
                        $context->getRunId(),
                        $context->getApplicationId(),
                        $context->getUniqueId(),
                        $context->getPeer()
                    ))->setArguments($context->getArguments());

                    // добавление потока в очередь выполнения
                    $this->threadRunQueue->push($thread);
                }
            ),
            new CommandHandler(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    // получение и обработка результата выполнения потока
                    $this->threadResult(
                        $this->workerPool->getById($context->getPeer()->getConnectionResource()->getId()),
                        $context->getRunId(),
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
        while (!$this->threadRunQueue->isEmpty()) {
            $thread = $this->threadRunQueue->shift();
            /**
             * @var $thread ControlledThread
             */
            try {
                $worker = $this->workerBalance->getLowLoadedWorker($thread);
                $this->threadRun($worker, $thread);
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
        $this->threadKnownIndex->add($thread);
        $worker->addThreadToKnownList($thread);
    }

    /**
     * Выполняет постановку задачи на выполнение потока указанному воркеру.
     *
     * @param Worker $worker Воркер, которому предстоит передать поток на выполнение
     * @param AbstractThread $sourceThread Исходный поток, который будет передан на выполнение
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

        /** @var $command ThreadRun */
        $this->commandDispatcher->create(ThreadRun::NAME, $worker->getClient())
            ->onError(function () use ($sourceThread) {
                Log::log('Error run task ' . $sourceThread->getUniqueId());
                throw new \RuntimeException('Error run thread.');
                //todo
            })
            /*->onSuccess(function () use ($worker, $sourceThread, $runThread) {
                // todo end check

            })*/
            ->run(ThreadRun::serializeThread($runThread));
    }

    /**
     * Выполняет обработку результата выполнения потока,
     * и перенаправляет его к исходному постановщику задачи.
     *
     * @param Worker $worker От кого пришёл результат
     * @param int $runId По какому потоку пришёл результат
     * @param mixed $result Результат выполнения потока
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

        $this->commandDispatcher->create(ThreadResult::NAME, $sourceThread->getThreadFrom())
            ->onError(function () {
                //todo
            })
            /*->onSuccess(function () use ($sourceThread, $runThread) {
            })*/
            ->run(ThreadResult::serializeTask($sourceThread));
    }

    /**
     * @return WorkerPool
     */
    public function getWorkerPool(): WorkerPool
    {
        return $this->workerPool;
    }

    /**
     * @return WorkerBalance
     */
    public function getWorkerBalance(): WorkerBalance
    {
        return $this->workerBalance;
    }

    /**
     * @return ControllerThreadPoolIndex
     */
    public function getThreadKnownIndex(): PoolOfUniqueThreads
    {
        return $this->threadKnownIndex;
    }

    /**
     * @return \SplQueue
     */
    public function getThreadRunQueue(): \SplStack
    {
        return $this->threadRunQueue;
    }

    /**
     * @return ControllerThreadPoolIndex
     */
    public function getThreadRunSources(): ControllerThreadPoolIndex
    {
        return $this->threadRunSources;
    }

    /**
     * @return ControllerThreadPoolIndex
     */
    public function getThreadRunWork(): ControllerThreadPoolIndex
    {
        return $this->threadRunWork;
    }

    /**
     * @return ThreadLinker
     */
    public function getThreadLinks(): ThreadLinker
    {
        return $this->threadLinks;
    }
}
