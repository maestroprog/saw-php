<?php

namespace Saw\Command;

use Saw\Thread\AbstractThread;

/**
 * Общая команда "Задача запущена".
 * От воркера отправляется контроллеру для постановки в очередь на запуск.
 * От контроллера отправляется воркеру в виде приказа для запуска задачи.
 *
 * Результат выполнения команды - успешный/неуспешный запуск выполнения задачи.
 */
class ThreadRun extends AbstractCommand
{
    const NAME = 'trun';

    protected $needData = ['run_id', 'unique_id'];

    public function handle(array $data)
    {
        parent::handle($data);
        if (isset($data['command_id'])) {
            $this->data['command_id'] = $data['command_id'];
        }
        if (isset($data['from_dsc'])) {
            $this->data['from_dsc'] = $data['from_dsc'];
        }
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getRunId(): int
    {
        return $this->data['run_id'];
    }

    public function getFromDsc()
    {
        return $this->data['from_dsc'] ?? null;
    }

    /**
     * Команда сама знает, что ей нужно знать о задаче
     * - поэтому дадим ей задачу, пускай возьмёт все, что ей нужно.
     *
     * @param AbstractThread $thread
     * @return array
     */
    public static function serializeTask(AbstractThread $thread): array
    {
        return [
            'run_id' => $thread->getId(),
            'unique_id' => $thread->getUniqueId(),
            'arguments' => $thread->getArguments(),
        ];
    }
}
