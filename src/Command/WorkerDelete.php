<?php

namespace Saw\Command;

/**
 * Общая команда "воркер остановлен".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера отправляется воркеру в виде приказа для остановки.
 *
 * Результат выполнения - успешный/неуспешный останов воркера;
 * успешное/неуспешое удаление воркера из числа известных.
 */
class WorkerDelete extends AbstractCommand
{
    const NAME = 'wdel';

    public function getCommand(): string
    {
        return self::NAME;
    }
}
