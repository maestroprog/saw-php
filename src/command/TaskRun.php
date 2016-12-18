<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 12.12.16
 * Time: 21:12
 */

namespace maestroprog\saw\command;

use maestroprog\saw\entity\Task;
use maestroprog\saw\library\dispatcher\Command;

/**
 * Общая команда "Задача запущена".
 * От воркера отправляется контроллеру для постановки в очередь на запуск.
 * От контроллера отправляется воркеру в виде приказа для запуска задачи.
 *
 * Результат выполнения команды - успешный/неуспешный запуск выполнения задачи.
 */
class TaskRun extends Command
{
    const NAME = 'trun';

    protected $needData = ['name', 'run_id'];

    public function getCommand(): string
    {
        return self::NAME;
    }

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
     * @param Task $task
     * @return array
     */
    public static function serializeTask(Task $task): array
    {
        return [
            'name' => $task->getName(),
            'run_id' => $task->getRunId(),
            'from_dsc' => $task->getPeerDsc(),
        ];
    }
}
