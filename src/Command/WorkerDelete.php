<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 30.11.16
 * Time: 22:41
 */

namespace maestroprog\saw\Command;

use maestroprog\saw\Heading\dispatcher\Command;

/**
 * Общая команда "воркер остановлен".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера отправляется воркеру в виде приказа для остановки.
 *
 * Результат выполнения - успешный/неуспешный останов воркера;
 * успешное/неуспешое удаление воркера из числа известных.
 */
class WorkerDelete extends Command
{
    const NAME = 'wdel';

    public function getCommand(): string
    {
        return self::NAME;
    }
}
