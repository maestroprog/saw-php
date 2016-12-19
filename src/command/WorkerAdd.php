<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 10:58
 */

namespace maestroprog\saw\command;

use maestroprog\saw\library\dispatcher\Command;

/**
 * Команда воркера "Воркер стартовал".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения - успешное/неуспешное добавление воркера в список известных.
 */
class WorkerAdd extends Command
{
    const NAME = 'wadd';

    public function getCommand(): string
    {
        return self::NAME;
    }
}
