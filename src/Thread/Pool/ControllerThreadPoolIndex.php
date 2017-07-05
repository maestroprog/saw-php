<?php

namespace Saw\Thread\Pool;

use Saw\Entity\Worker;
use Saw\Thread\AbstractThread;

class ControllerThreadPoolIndex
{
    private $threads;
    private $workers;

    public function __construct()
    {
        $this->threads = [];
    }

    /**
     * Добавляет информацию о потоке и воркере в индекс.
     *
     * @param Worker $worker
     * @param AbstractThread $thread
     */
    public function add(Worker $worker, AbstractThread $thread)
    {
        $this->threads[$thread->getId()][$worker->getId()] = 1;
        $this->workers[$worker->getId()][$thread->getId()] = $thread;
    }

    public function getThread(Worker $worker, int $threadRunId): AbstractThread
    {
        return $this->workers[$worker->getId()][$threadRunId];
    }
}
