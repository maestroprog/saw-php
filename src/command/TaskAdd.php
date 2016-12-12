<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 12.12.16
 * Time: 21:08
 */

namespace maestroprog\saw\command;

use maestroprog\saw\library\Command;

/**
 * Команда "Задача добавлена".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 */
class TaskAdd extends Command
{
    const NAME = 'tadd';

    private $data;

    public function getData(): array
    {
        return $this->data;
    }

    public function getCommand(): string
    {
        return self::NAME;
    }

    public function handle(array $data)
    {
        if (!isset($data['name'])) {
            throw new \Exception('PIZDES');
        }
        $this->data['name'] = $data['name'];
    }
}
