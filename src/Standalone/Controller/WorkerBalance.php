<?php

namespace Saw\Standalone\Controller;

use Esockets\debug\Log;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadRun;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Entity\Worker;
use Saw\Service\CommandDispatcher;
use Saw\Service\WorkerStarter;
use Saw\ValueObject\ProcessStatus;

/**
 * Балансировщик воркеров.
 * Ответственность: наблюдение за работой воркеров.
 */
class WorkerBalance implements CycleInterface
{
    const WORKER_MIN = 2;

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
     * @var ProcessStatus
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
            new CommandHandler(WorkerAdd::NAME, WorkerAdd::class, function (WorkerAdd $context) {
                if (is_null($this->workerRun) || !$this->workerRun instanceof ProcessStatus) {
                    throw new \LogicException('Некорректный состояние запуска воркера.');
                }
                if ($context->getPid() !== $this->workerRun->getPid()) {
                    // если pid запущенного процесса не соответсвует pid-у который сообщил воркер
                    return false;
                }
                $this->workerPool->add(new Worker($this->workerRun, $context->getPeer()));
                return true;
            }),
            new CommandHandler(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (WorkerDelete $context) {
                    $this->workerPool->removeById((int)$context->getPeer()->getConnectionResource()->getResource());
                }
            ),
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
        $this->server->getPeerByDsc($dsc)->send(['command' => 'wdel']);
        unset($this->workers[$dsc]);
        // TODO
        // нужно запилить механизм перехвата невыполненных задач
        foreach ($this->tasks as $name => $workers) {
            if (isset($workers[$dsc])) {
                unset($this->tasks[$name][$dsc]);
            }
        }
    }

    /**
     * Функция выбирает наименее загруженный воркер.
     *
     * @param string $name задача
     * @param callable|null $filter ($data)
     * @return int
     */
    private function wMinT($name, callable $filter = null): int
    {
        $selectedDsc = -1;
        foreach ($this->workers as $dsc => $worker) {
            if (!is_null($filter) && !$filter($worker)) {
                // отфильтровали
                continue;
            }
            if (!$worker->isKnowTask($this->taskAssoc[$name])) {
                // не знает такую задачу
                continue;
            }
            if (!isset($count)) {
                $count = $worker->getCountTasks();
                $selectedDsc = $dsc;
            } elseif ($count > ($newCount = $worker->getCountTasks())) {
                $count = $newCount;
                $selectedDsc = $dsc;
            }
        }
        return $selectedDsc;
    }

    /**
     * Получить одного из слабо нагруженных воркеров.
     *
     * @param ThreadRun $threadCommand Задача для которой необходимо найти воркера.
     * @return Worker
     */
    public function getLowLoadedWorker(ThreadRun $threadCommand): Worker
    {
        $selectedDsc = -1;
        foreach ($this->workerPool as $worker) {
            /*if (!is_null($filter) && !$filter($worker)) {
                // отфильтровали
                continue;
            }*/
            /**
             * @var $worker Worker
             */
            if (!$worker->isThreadKnow($threadCommand)) {
                // не знает такую задачу
                continue;
            }
            if (!isset($count)) {
                $count = $worker->getCountRunThreads();
                $selectedDsc = $dsc;
            } elseif ($count > ($newCount = $worker->getCountRunThreads())) {
                $count = $newCount;
                $selectedDsc = $dsc;
            }
        }
        return $selectedDsc;
    }
}
