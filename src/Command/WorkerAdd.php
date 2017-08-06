<?php

namespace Maestroprog\Saw\Command;

/**
 * Команда воркера "Воркер стартовал".
 * От воркера отправляется контроллеру для извещения.
 * От контроллера такая команда не отправляется (пока такое не предусмотрено).
 *
 * Результат выполнения - успешное/неуспешное добавление воркера в список известных.
 */
class WorkerAdd extends AbstractCommand
{
    const NAME = 'wadd';

    protected $needData = ['pid'];

    /**
     * Вернёт pid, о котором сообщил воркер.
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->data['pid'];
    }
}
