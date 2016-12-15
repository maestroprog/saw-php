<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 12.12.16
 * Time: 21:12
 */

namespace maestroprog\saw\command;

use maestroprog\saw\entity\Task;
use maestroprog\saw\library\Command;

/**
 * Общая команда "Задача запущена".
 * От воркера отправляется контроллеру для постановки в очередь на запуск.
 * От контроллера отправляется воркеру в виде приказа для запуска задачи.
 */
class TaskRun extends Command
{
    const NAME = 'trun';

    private $data;

    public function & getData(): array
    {
        return $this->data;
    }

    public function getCommand(): string
    {
        return self::NAME;
    }

    public function handle(array $data)
    {
        if (!isset($data['name']) || !isset($data['result'])) {
            throw new \Exception('Cannot handle, empty data');
        }
        $this->data = $data;
    }

    public function isValid(): bool
    {
        return isset($this->data['name']);
    }

    /**
     * Команда сама знает, что ей нужно знать о задаче
     * - поэтому дадим ей задачу, пускай возьмёт все, что ей нужно.
     *
     * @param Task $task
     * @return array
     */
    public static function serializeTask(Task $task): array
    {
        return [
            'name' => $task->getName(),
            $task->getState()
        ];
    }
}
