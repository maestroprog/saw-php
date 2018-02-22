<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

/**
 * Команда "Новый известный поток".
 * От воркера отправляется контроллеру для извещения о новом потоке, который воркер узнал.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения команды - успешное/неуспешное добавление в известные команды.
 */
final class ThreadKnow extends AbstractCommand
{
    const NAME = 'tadd';

    private $applicationId;
    private $uniqueId;

    public function __construct(Client $client, string $appId, string $uniqueId)
    {
        parent::__construct($client);
        $this->applicationId = $appId;
        $this->uniqueId = $uniqueId;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['application_id'], $data['unique_id']);
    }

    /**
     * Вернёт уникальный идентификатор приложения,
     * к которому относится поток.
     *
     * @return string
     */
    public function getApplicationId(): string
    {
        return $this->applicationId;
    }

    /**
     * Вернёт уникальный ID потока.
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function toArray(): array
    {
        return ['application_id' => $this->applicationId, 'unique_id' => $this->uniqueId];
    }
}
