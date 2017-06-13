<?php

namespace Saw\Standalone\Controller;

use Esockets\debug\Log;
use Saw\Command\ThreadRun;
use Saw\Entity\Worker;
use Saw\Service\WorkerStarter;

/**
 * Балансировщик воркеров.
 * Ответственность: наблюдение за работой воркеров.
 */
class WorkerBalance implements CycleInterface
{
    private $workerStarter;
    private $workerMaxCount;
    private $workerMultiplier;

    private $workerPool;

    /**
     * Известные воркерам задачи.
     *
     * @var bool[][] $name => $dsc => true
     */
    private $tasksKnow = [];

    /**
     * @var int Состояние запуска нового воркера.
     */
    private $running = 0;


    public function __construct(
        WorkerStarter $workerStarter,
        int $workerMaxCount
    )
    {
        $this->workerStarter = $workerStarter;
        $this->workerMaxCount = $workerMaxCount;
        $this->workerPool = new WorkerPool();
    }

    /**
     * Предполагается, что этот метод будет запускать новые воркеры, когда это нужно.
     */
    public function work()
    {
        if (count($this->workerPool) < $this->workerMaxCount && !$this->running) {
            // run new worker
            $this->workerStarter->start();
            $this->running = time();
        } elseif ($this->running && $this->running < time() - 10) {
            // timeout 10 sec
            $this->running = 0;
            Log::log('Can not run worker!');
        } else {
            // so good; всё ок
        }
    }


    private function getWorkerByDsc(int $dsc): Worker
    {
        return $this->workers[$dsc] ?? null;
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
     * @param ThreadRun $thread Задача для которой необходимо найти воркера.
     * @return Worker
     */
    public function getLowLoadedWorker(ThreadRun $thread): Worker
    {

    }
}
