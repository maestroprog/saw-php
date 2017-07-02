<?php

namespace Saw\Command;

use Saw\Thread\AbstractThread;

/**
 * Общая команда "Результат выполнения задачи".
 * В любом случае отправляется для передачи результата выполнения задачи
 * конечному получателю - тому кто ставил задачу.
 *
 * Результат выполнения команды - успешный/неуспешный прием результата выполнения задачи.
 */
class ThreadResult extends AbstractCommand
{
    const NAME = 'tres';

    protected $needData = ['run_id', 'result'];

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
     * @param AbstractThread $thread
     * @return array
     */
    public static function serializeTask(AbstractThread $thread): array
    {
        return [
            'run_id' => $thread->getRunId(),
            'from_dsc' => $thread->getPeerDsc(),
            'result' => $thread->getResult(),
        ];
    }
}
