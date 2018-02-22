<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

/**
 * Общая команда "Результат выполнения потока".
 * В любом случае отправляется для передачи результата выполнения потока
 * конечному получателю - тому кто ставил поток на выполнение.
 *
 * Результат выполнения команды - успешный/неуспешный прием результата выполнения потока.
 */
final class ThreadResult extends AbstractCommand
{
    const NAME = 'tres';

    private $runId;
    private $applicationId;
    private $uniqueId;
    private $result;

    public function __construct(
        Client $client,
        int $runId,
        string $appId,
        string $uniqueId,
        $result
    )
    {
        parent::__construct($client);
        $this->runId = $runId;
        $this->applicationId = $appId;
        $this->uniqueId = $uniqueId;
        $this->result = $result;
    }

    public function getRunId(): int
    {
        return $this->runId;
    }

    public function getApplicationId(): string
    {
        return $this->applicationId;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * WARNING! Переопределяет родительский метод.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    public function toArray(): array
    {
        return [
            'run_id' => $this->getRunId(),
            'application_id' => $this->getApplicationId(),
            'unique_id' => $this->getUniqueId(),
            'result' => $this->getResult(),
        ];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self(
            $client,
            $data['run_id'],
            $data['application_id'],
            $data['unique_id'],
            $data['result']
        );
    }
}
