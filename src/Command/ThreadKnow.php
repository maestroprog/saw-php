<?php

namespace Saw\Command;

/**
 * Команда "Задача добавлена".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения команды - успешное/неуспешное добавление в известные команды.
 */
class ThreadKnow extends AbstractCommand
{
    const NAME = 'tadd';

    protected $needData = ['name'];

    public function getCommand(): string
    {
        return self::NAME;
    }
}