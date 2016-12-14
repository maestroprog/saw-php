<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 30.11.16
 * Time: 22:41
 */

namespace maestroprog\saw\command;

use maestroprog\saw\library\Command;

/**
 * Общая команда "воркер остановлен".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера отправляется воркеру в виде приказа для остановки.
 */
class WorkerDelete extends Command
{
    const NAME = 'wdel';

    public function getData(): array
    {
        return [];
    }

    public function getCommand(): string
    {
        return self::NAME;
    }

    public function handle(array $data)
    {
    }

    public function isValid(): bool
    {
        return true;
    }
}
