<?php

namespace Maestroprog\Saw\Command;

use Maestroprog\Saw\Thread\AbstractThread;

/**
 * Общая команда "Результат выполнения потока".
 * В любом случае отправляется для передачи результата выполнения потока
 * конечному получателю - тому кто ставил поток на выполнение.
 *
 * Результат выполнения команды - успешный/неуспешный прием результата выполнения потока.
 */
class ThreadResult extends AbstractCommand
{
    const NAME = 'tres';

    protected $needData = ['run_id', 'application_id', 'unique_id', 'result'];

    public function getRunId(): int
    {
        return $this->data['run_id'];
    }

    public function getApplicationId(): string
    {
        return $this->data['application_id'];
    }

    public function getUniqueId(): string
    {
        return $this->data['unique_id'];
    }

    public function getResult()
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
            'run_id' => $thread->getId(),
            'application_id' => $thread->getApplicationId(),
            'unique_id' => $thread->getUniqueId(),
            'result' => $thread->getResult(),
        ];
    }
}
