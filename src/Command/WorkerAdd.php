<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

/**
 * Команда воркера "Воркер стартовал".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения - успешное/неуспешное добавление воркера в список известных.
 */
final class WorkerAdd extends AbstractCommand
{
    const NAME = 'wadd';

    private $pid;

    public function __construct(Client $client, int $pid)
    {
        parent::__construct($client);
        $this->pid = $pid;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['pid']);
    }

    /**
     * Вернёт pid, о котором сообщил воркер.
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    public function toArray(): array
    {
        return ['pid' => $this->pid];
    }
}
