<?php

namespace Saw\Heading\worker;

use Esockets\TcpClient;
use Saw\Entity\Task;
use Saw\Heading\Application;
use Saw\Heading\TaskManager;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class WorkerCore
{
    private $peer;

    /**
     * @var TaskManager
     */
    private $taskManager;

    private $appClass;

    /**
     * @var Application
     */
    public $app;

    public function __construct(
        TcpClient $peer,
        string $workerAppClass
    )
    {
        $this->peer = $peer;
        $this->appClass = $workerAppClass;
    }

    /**
     * Настраивает текущий таск-менеджер.
     *
     * @param TaskManager $taskManager
     * @return $this
     */
    public function setTaskManager(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
        return $this;
    }

    /**
     * Запускает приложение, которое привязано к воркеру.
     *
     * @throws \Exception
     */
    public function run()
    {
        if (!$this->taskManager) {
            throw new \Exception('Cannot run worker!');
        }
        $this->app = new $this->appClass($this->taskManager);
        if (!$this->app instanceof Application) {
            throw new \Exception('Worker application must be instance of Saw\library\Application');
        }
        $this->app->run();
    }

    /**
     * @var array
     */
    private $knowTasks = [];

    /**
     * Оповещает контроллер о том, что данный воркер узнал новую задачу.
     * Контроллер (и сам воркер) запоминает это.
     *
     * @param Task $task
     */
    public function addTask(Task $task)
    {
        if (!isset($this->knowTasks[$task->getName()])) {
            $this->knowTasks[$task->getName()] = 1;
        }
    }

    /**
     * @var Task[]
     */
    private $runQueue = [];

    /**
     * Постановка задачи в очередь на выполнение.
     *
     * @param Task $task
     */
    public function runTask(Task $task)
    {
        $this->runQueue[] = $task;
    }

    public function runCallback(string $name)
    {
        return $this->taskManager->runCallback($name);
    }

    public function & getRunQueue(): array
    {
        return $this->runQueue;
    }

    /**
     * Принимает от контроллера результат выполненной задачи.
     *
     * @param int $rid
     * @param $result
     */
    public function receiveTask(int $rid, &$result)
    {
        $task = $this->taskManager->getRunTask($rid);
        $task->setResult($result);
    }
}
