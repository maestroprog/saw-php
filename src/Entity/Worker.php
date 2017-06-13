<?php

namespace Saw\Entity;

use Esockets\Client;
use Saw\Dto\ProcessStatus;

/**
 * Сущность воркера, которой оперирует контроллер.
 * Зеркалирует состояние воркера, при этом управляется только извне.
 */
class Worker
{
    const NEW = 0; // новый воркер
    const READY = 1; // воркер, готовый к выполнению задач
    const RUN = 2; // воркер, выполняющий задачу
    const STOP = 3; // воркер, остановивший работу
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

    private $processResource;
    private $client;

    /**
     * Состояние воркера.
     *
     * @var int
     */
    private $state;

    /**
     * @param ProcessStatus $processResource
     * @param Client $client
     * @param int $state
     */
    public function __construct(ProcessStatus $processResource, Client $client, int $state = self::NEW)
    {
        $this->processResource = $processResource;
        $this->client = $client;
        $this->state = $state;
    }

    public function getId(): int
    {
        return (int)$this->client->getConnectionResource()->getResource();
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function isKnowTask(int $tid): bool
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

    public function getTask(int $runId): Task
    {
        return $this->runTasks[$runId] ?? null;
    }

    public function removeTask(Task $task)
    {
        if (isset($this->runTasks[$task->getRunId()])) {
            unset($this->runTasks[$task->getRunId()]);
        }
    }

    public function getCountTasks()
    {
        return count($this->runTasks);
    }
}
