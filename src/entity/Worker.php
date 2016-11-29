<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 28.11.16
 * Time: 20:18
 */

namespace maestroprog\saw\entity;


use maestroprog\esockets\Peer;

class Worker
{
    const NEW = 0; // новый воркер
    const READY = 1; // воркер, готовый к выполнению задач
    const RUN = 2; // воркер, выполняющий задачу
    const STOP = 3; // воркер, остановивший работу

    /**
     * @var Peer
     */
    private $peer;

    /**
     * Состояние воркера.
     *
     * @var int
     */
    private $state;

    /**
     * Задачи, которые знает воркер (по TID).
     *
     * @var int[]
     */
    private $knowTasks = [];
    /**
     * Задачи, которые выполняет воркер (по RID).
     *
     * @var Task[]
     */
    private $runTasks = [];

    public function __construct(Peer $peer, int $state = self::NEW)
    {
        $this->peer = $peer;
        $this->state = $state;
        $peer->send(['command' => 'wadd', 'result' => true]);
    }

    public function getState() : int
    {
        return $this->state;
    }

    public function isKnowTask(int $tid) : bool
    {
        return in_array($tid, $this->knowTasks);
    }

    public function addKnowTask(int $tid)
    {
        $this->knowTasks[$tid] = $tid;
    }

    public function addTask(Task $task)
    {
        $this->runTasks[$task->getRunId()] = $task;
    }

    public function getCountTasks()
    {
        return count($this->runTasks);
    }
}
