<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 10.12.2016
 * Time: 14:52
 */

namespace maestroprog\library\worker;

use maestroprog\esockets\TcpClient;
use maestroprog\saw\entity\Task;
use maestroprog\saw\library\Application;
use maestroprog\saw\library\TaskManager;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class Core
{
    private $peer;

    /**
     * @var TaskManager
     */
    private $taskManager;

    /**
     * @var Application
     */
    private $app;

    public function __construct(
        TcpClient $peer,
        string $workerApp,
        string $workerAppClass
    )
    {
        $this->peer = $peer;
        if (empty($workerApp) || !file_exists($workerApp)) {
            throw new \Exception('Worker application configuration not found');
        }
        require_once $workerApp;
        if (!class_exists($workerAppClass)) {
            throw new \Exception('Worker application must be configured with "worker_app_class"');
        }
        $this->app = new $workerAppClass();
        if (!$this->app instanceof Application) {
            throw new \Exception('Worker application must be instance of maestroprog\saw\library\Application');
        }
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

    public function run()
    {
        if (!$this->taskManager) {
            throw new \Exception('Cannot run worker!');
        }
        $this->app->run($this->taskManager);
    }

    /**
     * @var array
     */
    private $knowTasks = [];

    /**
     * Оповещает контроллер о том, что данный воркер узнал новую задачу.
     * Контроллер запоминает это.
     *
     * @param Task $task
     */
    public function addTask(Task $task)
    {
        if (!isset($this->knowTasks[$task->getName()])) {
            $this->knowTasks[$task->getName()] = 1;
        }
    }

    public function runTask(string $name)
    {
        return $this->taskManager->runCallback($name);
    }
}