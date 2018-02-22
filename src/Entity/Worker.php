<?php

namespace Maestroprog\Saw\Entity;

use Esockets\Client;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\Pool\PoolOfUniqueThreads;
use Maestroprog\Saw\ValueObject\ProcessStatus;

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
     * @var AbstractThread[]
     */
    private $runThreads = [];

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

        $this->knowThreads = new PoolOfUniqueThreads();
    }

    /**
     * Вернёт ID воркера.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->client->getConnectionResource()->getId();
    }

    /**
     * @return ProcessStatus
     */
    public function getProcessResource(): ProcessStatus
    {
        return $this->processResource;
    }

    /**
     * Вернёт клиента, с помощью которого можно общаться с воркером.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Проверяет, знает ли воркер указанный поток.
     *
     * @param AbstractThread $thread
     *
     * @return bool
     */
    public function isThreadKnow(AbstractThread $thread): bool
    {
        return $this->knowThreads->exists($thread->getUniqueId());
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
        $this->runThreads[$thread->getId()] = $thread;
    }

    public function getRunThread(int $runId): AbstractThread
    {
        return $this->runThreads[$runId];
    }

    public function removeRunThread(AbstractThread $thread)
    {
        if (isset($this->runThreads[$thread->getId()])) {
            unset($this->runThreads[$thread->getId()]);
        }
    }

    public function getCountRunThreads()
    {
        return count($this->runThreads);
    }
}
