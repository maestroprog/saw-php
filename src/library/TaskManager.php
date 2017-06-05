<?php

namespace Saw\Heading;

use Saw\Entity\Task;
use Saw\Connector\Main;
use Saw\Standalone\Worker;

/**
 * Менеджер задач для воркера.
 * Занимается созданием задач,
 * присвоения им сгенерированного айдишника,
 * и проксированием к контроллеру.
 * @version 0.1-dev
 * Описание относится именно к данной версии.
 * @todo Нужно окружение.
 */
class TaskManager extends Singleton
{
    /**
     * @var Worker|Main
     */
    protected $controller;

    private $link = [];

    /**
     * Запуск задачи.
     *
     * @param callable $callback
     * @param string $name
     * @return Task запущенная задача.
     */
    public function run(callable $callback, string $name)
    {
        $this->link($callback, $name);
        $task = $this->createTask($name);
        try {
            $this->controller->addTask($task);
        } catch (\Exception $e) {
            // если не удалось передать задачу в отдельный процесс - сами выполняем её.
            $task->setResult($this->runCallback($name));
        }
        return $task;
    }

    /**
     * Некий вид прерывания - ожидание завершения выполнения указанных задач.
     * Вернет true в случае успешного завершения всех задач,
     * false - в случае ошибки выполнения любой из задач.
     *
     * @param $tasks Task[]
     * @return bool
     */
    public function sync(array $tasks, float $timeout = 0.1): bool
    {
        return $this->controller->syncTask($tasks, $timeout);
    }

    /**
     * Метод запускает выполнение известной менеджеру задачи.
     *
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function runCallback(string $name)
    {
        if (!isset($this->link[$name])) {
            throw new \Exception('Callback not found ' . $name);
        }
        return call_user_func($this->link[$name]);
    }

    public function getRunTask(int $rid): Task
    {
        return $this->run[$rid] ?? null;
    }

    public function setController(TaskRunner $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /* PRIVATE FUNCTIONS */

    /**
     * @param callable $callback
     * @param string $name
     */
    private function link(callable &$callback, string $name)
    {
        if (!isset($this->link[$name])) {
            $this->link[$name] = &$callback;
        }
    }

    /**
     * @var Task[]
     */
    private $run = [];

    /**
     * @param string $name
     * @return Task
     */
    private function createTask(string $name): Task
    {
        static $rid = 0;
        $rid++;
        return $this->run[$rid] = new Task($rid, $name);
    }
}
