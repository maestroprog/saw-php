<?php

namespace Saw\Entity;

use Esockets\Client;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\WorkerThreadPool;
use Saw\ValueObject\ProcessStatus;

/**
 * Сущность воркера, которой оперирует контроллер.
 * Зеркалирует состояние воркера, при этом управляется только извне.
 */
class Worker
{
    const READY = 1; // воркер, готовый к выполнению задач
    const RUN = 2; // воркер, выполняющий задачу
    const STOP = 3; // воркер, остановивший работу

    /**
     * Задачи, которые знает воркер (по TID).
     *
     * @var
     */
    private $knowThreads;

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
    public function __construct(ProcessStatus $processResource, Client $client, int $state = self::READY)
    {
        $this->processResource = $processResource;
        $this->client = $client;
        $this->state = $state;

        $this->knowThreads = new WorkerThreadPool();
    }

    /**
     * Вернёт ID воркера.
     *
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->client->getConnectionResource()->getResource();
    }

    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Проверяет, знает ли воркер указанный поток.
     *
     * @param AbstractThread $thread
     * @return bool
     */
    public function isThreadKnow(AbstractThread $thread): bool
    {
        return $this->knowThreads->existsThreadByUniqueId($thread->getUniqueId());
    }

    /**
     * Добавляет поток в список известных воркеру.
     *
     * @param AbstractThread $thread
     */
    public function addThreadToKnownList(AbstractThread $thread)
    {
        $this->knowThreads->add($thread);
    }

    public function addThreadToRunList(AbstractThread $thread)
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
