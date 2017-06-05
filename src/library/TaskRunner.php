<?php

namespace Saw\Heading;

use Saw\Entity\Task;

interface TaskRunner
{
    /**
     * Запускает задачу.
     *
     * @param Task $Task
     * @return mixed
     */
    public function addTask(Task $Task);

    /**
     * Ожидает завершение выполнения указанных задач.
     *
     * @param array $tasks
     * @param float $timeout
     * @return bool
     */
    public function syncTask(array $tasks, float $timeout = 0.1): bool;

    /**
     * Настраивает менеджер задач.
     *
     * @param TaskManager $taskManager
     * @return mixed
     */
    public function setTaskManager(TaskManager $taskManager);
}
