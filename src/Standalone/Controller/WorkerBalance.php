<?php

namespace Saw\Standalone\Controller;

use Esockets\debug\Log;
use Saw\Command\CommandHandler;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Entity\Worker;
use Saw\Service\CommandDispatcher;
use Saw\Service\WorkerStarter;
use Saw\Thread\AbstractThread;
use Saw\ValueObject\ProcessStatus;

/**
 * Балансировщик воркеров.
 * Ответственность: наблюдение за работой воркеров.
 */
class WorkerBalance implements CycleInterface
{
    const WORKER_MIN = 8;

    private $workerStarter;
    private $commandDispatcher;
    private $workerPool;
    private $workerMaxCount;

    private $workerMultiplier;

    /**
     * @var int Состояние запуска нового воркера.
     */
    private $running = 0;

    /**
     * @var ProcessStatus|null
     */
    private $workerRun;


    public function __construct(
        WorkerStarter $workerStarter,
        CommandDispatcher $commandDispatcher,
        WorkerPool $workerPool,
        int $workerMaxCount
    )
    {
        $this->workerStarter = $workerStarter;
        $this->commandDispatcher = $commandDispatcher;
        $this->workerPool = $workerPool;
        $this->workerMaxCount = $workerMaxCount;

        $commandDispatcher->add([
            new CommandHandler(WorkerAdd::class, function (WorkerAdd $context) {
                if (is_null($this->workerRun) || !$this->workerRun instanceof ProcessStatus) {
                    throw new \LogicException('Некорректное состояние запуска воркера.');
                }
                /*if ($context->getPid() !== $this->workerRun->getPid()) {
                    // если pid запущенного процесса не соответсвует pid-у который сообщил воркер
                    return false;
                }*/
                $this->addWorker(new Worker($this->workerRun, $context->getPeer()));
                return true;
            }),
            new CommandHandler(
                WorkerDelete::class, function (WorkerDelete $context) {
                $this->workerPool->removeById($context->getPeer()->getConnectionResource()->getId());
            }),
        ]);
    }

    /**
     * Предполагается, что этот метод будет запускать новые воркеры, когда это нужно.
     * Воркеры запускаются по одному, после запуска контроллер ждёт новый воркер до 10 секунд.
     */
    public function work()
    {
        $forceRunWorker = false;

        if (
            (count($this->workerPool) < self::WORKER_MIN || $forceRunWorker)
            && !$this->running
        ) {
            // run new worker
            $this->workerRun = $this->workerStarter->start();
            $this->running = time();
        } elseif ($this->running && $this->running < time() - 10) {
            // timeout 10 sec - не удалось запустить воркер
            $this->running = 0;
            if ($this->workerRun->isRunning()) {
                // убиваем запущенный процесс, если он ещё работает
                $this->workerRun->kill();
            }
            Log::log('Can not run worker!');
        } else {
            // so good; всё ок
        }
    }

    /**
     * Добавляет воркер в балансировщик,
     * когда тот успешно стартует.
     *
     * @param Worker $worker
     * @return bool
     */
    public function addWorker(Worker $worker): bool
    {
        if (!$this->running) {
            return false;
        }
        $this->running = 0;
        $this->workerPool->add($worker);
        return true;
    }

    /**
     * Удаляет воркер из балансировщика.
     *
     * @param Worker $worker
     */
    public function removeWorker(Worker $worker)
    {
        $this->commandDispatcher->create(WorkerDelete::NAME, $worker->getClient())->run();
        $this->workerPool->remove($worker);
        // TODO
        // нужно запилить механизм перехвата невыполненных задач
        /*foreach ($this->tasks as $name => $workers) {
            if (isset($workers[$dsc])) {
                unset($this->tasks[$name][$dsc]);
            }
        }*/
    }

    /**
     * Получить одного из слабо нагруженных воркеров.
     *
     * @param AbstractThread $thread Поток для выполнения которого необходимо найти воркера.
     * @return Worker
     * @throws \RuntimeException
     */
    public function getLowLoadedWorker(AbstractThread $thread): Worker
    {
        $selectedWorker = null;
        foreach ($this->workerPool as $worker) {
            /*if (!is_null($filter) && !$filter($worker)) {
                // отфильтровали
                continue;
            }*/
            /**
             * @var $worker Worker
             */
            if ($worker->getState() === Worker::STOP || !$worker->isThreadKnow($thread)) {
                // не знает такую задачу
                continue;
            }
            if (!isset($count)) {
                $count = $worker->getCountRunThreads();
                $selectedWorker = $worker;
            } elseif ($count > ($newCount = $worker->getCountRunThreads())) {
                $count = $newCount;
                $selectedWorker = $worker;
            }
        }
        if (is_null($selectedWorker)) {
            throw new \RuntimeException('Cannot select worker.');
        }
        return $selectedWorker;
    }
}
