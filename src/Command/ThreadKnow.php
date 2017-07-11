<?php

namespace Saw\Command;

/**
 * Команда "Новый известный поток".
 * От воркера отправляется контроллеру для извещения о новом потоке, который воркер узнал.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения команды - успешное/неуспешное добавление в известные команды.
 */
class ThreadKnow extends AbstractCommand
{
    const NAME = 'tadd';

    protected $needData = ['application_id', 'unique_id'];

    /**
     * Вернёт уникальный идентификатор приложения,
     * к которому относится поток.
     *
     * @return string
     */
    public function getApplicationId(): string
    {
        return $this->data['application_id'];
    }

    /**
     * Вернёт уникальный ID потока.
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->data['unique_id'];
    }
}
