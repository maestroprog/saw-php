<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

/**
 * Общая команда "воркер остановлен".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера отправляется воркеру в виде приказа для остановки.
 *
 * Результат выполнения - успешный/неуспешный останов воркера;
 * успешное/неуспешое удаление воркера из числа известных.
 */
final class WorkerDelete extends AbstractCommand
{
    const NAME = 'wdel';

    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    public function toArray(): array
    {
        return [];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client);
    }
}
