<?php

namespace Saw\Thread\Pool;

use Saw\Entity\Worker;
use Saw\Thread\AbstractThread;

class ControllerThreadPoolIndex
{
    private $threads;

    /**
     * Добавляет информацию о потоке и воркере в индекс.
     *
     * @param Worker $worker
     * @param AbstractThread $thread
     */
    public function add(Worker $worker, AbstractThread $thread)
    {

    }
}
