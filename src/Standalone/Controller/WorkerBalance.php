<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\debug\Log;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\WorkerAdd;
use Maestroprog\Saw\Command\WorkerDelete;
use Maestroprog\Saw\Entity\Worker;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Service\WorkerStarter;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\ValueObject\ProcessStatus;

/**
 * Балансировщик воркеров.
 * Ответственность: наблюдение за работой воркеров.
 */
class WorkerBalance implements CycleInterface
{
    const WORKER_MIN = 3;

    private $workerStarter;
    private $commandDispatcher;
    private $commander;
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
        Commander $commander,
        WorkerPool $workerPool,
        int $workerMaxCount,
        int $workerMultiplier
    )
    {
        $this->workerStarter = $workerStarter;
        $this->commandDispatcher = $commandDispatcher;
        $this->commander = $commander;
        $this->workerPool = $workerPool;
        $this->workerMaxCount = $workerMaxCount;
        $this->workerMultiplier = $workerMultiplier;

        $commandDispatcher->addHandlers([
            new CommandHandler(WorkerAdd::class, function (WorkerAdd $context) {
                if (!$this->workerRun instanceof ProcessStatus) {
                    throw new \LogicException('Некорректное состояние запуска воркера.');
                }
                /*if ($context->getPid() !== $this->workerRun->getPid() + 1) {
                    // если pid запущенного процесса не соответсвует pid-у который сообщил воркер
                    // может быть ситуация, когда в системе закончились pid-ы и нумерация сбросилась
                    // или между созданием процессов вклинился ещё один процесс
                    throw new \RuntimeException('Unknown worker');
                }*/
                $newWorker = $worker = new Worker($this->workerRun, $context->getClient());
                if (!$this->addWorker($newWorker)) {
                    $this->removeWorker($newWorker);
                    Log::log('Не удалось добавить воркера!');
                }
            }),
            new CommandHandler(WorkerDelete::class, function (WorkerDelete $context) {
                $this->workerPool->removeById($context->getClient()->getConnectionResource()->getId());
            }),
        ]);
    }

    /**
     * Предполагается, что этот метод будет запускать новые воркеры, когда это нужно.
     * Воркеры запускаются по одному, после запуска контроллер ждёт новый воркер до 10 секунд.
     */
    public function work()
    {
        $workerNeed = self::WORKER_MIN * $this->workerMultiplier;
        $workerCount = count($this->workerPool);

        if (
            ($workerCount < $workerNeed && $workerCount < $this->workerMaxCount)
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
        $this->commander->runAsync(new WorkerDelete($worker->getClient()));
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
