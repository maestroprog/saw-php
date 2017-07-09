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

    protected $needData = ['application_id', 'unique_id', 'run_id'];

    public function handle(array $data): AbstractCommand
    {
        /*foreach ($this->needData as $key) {
            if (isset($data[$key])) {
                $this->data[$key] = $data[$key];
            }
        }*/
        if (isset($data['arguments'])) {
            $this->data['arguments'] = $data['arguments'];
        }
        return parent::handle($data);
    }

    public function getApplicationId(): string
    {
        return $this->data['application_id'];
    }

    public function getUniqueId(): string
    {
        return $this->data['unique_id'];
    }

    public function getRunId(): int
    {
        return $this->data['run_id'];
    }

    public function getFromDsc()
    {
        return $this->data['from_dsc'] ?? null;
    }

    public function getArguments(): array
    {
        return $this->data['arguments'] ?? [];
    }

    /**
     * Команда сама знает, что ей нужно знать о задаче
     * - поэтому дадим ей задачу, пускай возьмёт все, что ей нужно.
     *
     * @param AbstractThread $thread
     * @return array
     */
    public static function serializeThread(AbstractThread $thread): array
    {
        return [
            'application_id' => $thread->getApplicationId(),
            'run_id' => $thread->getId(),
            'unique_id' => $thread->getUniqueId(),
            'arguments' => $thread->getArguments(),
        ];
    }
}
