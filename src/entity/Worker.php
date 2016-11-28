<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 28.11.16
 * Time: 20:18
 */

namespace maestroprog\saw\entity;


class Worker
{
    const NEW = 0; // новый воркер
    const READY = 1; // воркер, готовый к выполнению задач
    const RUN = 2; // воркер, выполняющий задачу
    const STOP = 3; // воркер, остановивший работу

    /**
     * Peer ID воркера.
     *
     * @var int
     */
    private $dsc;

    /**
     * Сетевой адрес воркера.
     *
     * @var string
     */
    private $address;

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

    public function __construct(int $dsc, string $address, int $state = self::READY)
    {
        $this->dsc = $dsc;
        $this->address = $address;
        $this->state = $state;
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