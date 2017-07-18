<?php

namespace Saw\Thread\Pool;

use Saw\Entity\Worker;
use Saw\Thread\AbstractThread;

class ControllerThreadPoolIndex implements \Countable
{
    private $threads;
    private $links;
    private $workers;

    public function __construct()
    {
        $this->threads = $this->links = $this->workers = [];
    }

    /**
     * Добавляет информацию о потоке и воркере в индекс.
     *
     * @param Worker $worker
     * @param AbstractThread $thread
     */
    public function add(Worker $worker, AbstractThread $thread)
    {
        $this->threads[$thread->getId()] = $worker->getId();
        $this->workers[$worker->getId()][$thread->getId()] = $thread;
    }

    public function getThread(Worker $worker, int $threadRunId): AbstractThread
    {
        return $this->workers[$worker->getId()][$threadRunId];
    }

    public function getThreadById(int $threadRunId): AbstractThread
    {
        $workerId = $this->threads[$threadRunId];
        $thread = $this->workers[$workerId][$threadRunId];
        return $thread;
    }

    public function getWorkerByThreadId(int $threadRunId): Worker
    {
        $workerId = $this->threads[$threadRunId];
        $worker = $this->workers[$workerId];
        return $worker;
    }

    public function removeThread(AbstractThread $thread)
    {
        if (isset($this->threads[$thread->getId()])) {
            $workerId = $this->threads[$thread->getId()];
            if (isset($this->workers[$workerId][$thread->getId()])) {
                unset($this->workers[$workerId][$thread->getId()]);
            }
            if (count($this->workers[$workerId]) === 0) {
                unset($this->workers[$workerId]);
            }
            unset($this->threads[$thread->getId()]);
        }
    }

    public function count()
    {
        return count($this->threads);
    }
}
