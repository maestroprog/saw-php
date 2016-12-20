<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 12.12.16
 * Time: 21:08
 */

namespace maestroprog\saw\command;

use maestroprog\saw\library\dispatcher\Command;

/**
 * Команда "Задача добавлена".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения команды - успешное/неуспешное добавление в известные команды.
 */
class TaskAdd extends Command
{
    const NAME = 'tadd';

    protected $needData = ['name'];

    public function getCommand(): string
    {
        return self::NAME;
    }
}
