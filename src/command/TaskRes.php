<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 16.12.16
 * Time: 13:14
 */

namespace maestroprog\saw\command;

use maestroprog\saw\entity\Task;
use maestroprog\saw\library\dispatcher\Command;

/**
 * Общая команда "Результат выполнения задачи".
 * В любом случае отправляется для передачи результата выполнения задачи
 * конечному получателю - тому кто ставил задачу.
 *
 * Результат выполнения команды - успешный/неуспешный прием результата выполнения задачи.
 */
class TaskRes extends Command
{
    const NAME = 'tres';

    protected $needData = ['run_id', 'result'];

    public function getCommand(): string
    {
        return self::NAME;
    }


    public function handle(array $data)
    {
        parent::handle($data);
        if (isset($data['from_dsc'])) {
            $this->data['from_dsc'] = $data['from_dsc'];
        }
    }

    public function getRunId(): int
    {
        return $this->data['run_id'];
    }

    public function getFromDsc()
    {
        return $this->data['from_dsc'] ?? null;
    }

    public function & getResult()
    {
        return $this->data['result'];
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
            'run_id' => $task->getRunId(),
            'from_dsc' => $task->getPeerDsc(),
            'result' => $task->getResult(),
        ];
    }
}
