<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:14
 */

namespace maestroprog\saw\library;

use maestroprog\saw\entity\Task;
use maestroprog\saw\service\Init;
use maestroprog\saw\service\Worker;

/**
 * Задача для воркера.
 * Представляет из себя изолированный от внешнего окружения объект.
 * @version 0.1-dev
 * Описание относится именно к данной версии.
 * @todo Нужно окружение.
 */
class TaskManager extends Singleton
{
    protected static $instance;
    /**
     * @var Worker|Init
     */
    protected $controller;

    private $link = [];

    /**
     * Запуск задачи.
     *
     * @param callable $callback
     * @param string $name
     * @return int ID запущенной задачи.
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
    public function sync(array $tasks): bool
    {
        return $this->controller->sync($tasks);
    }

    public function runCallback(string $name)
    {
        if (!isset($this->link[$name])) {
            throw new \Exception('Callback not found ' . $name);
        }
        return call_user_func($this->link[$name]);
    }

    public function setController(Worker $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    private function link(callable &$callback, string $name)
    {
        if (!isset($this->link[$name])) {
            $this->link[$name] = &$callback;
        }
    }

    private function createTask(string $name): Task
    {
        static $rid = 0;
        $rid++;
        return new Task($rid, $name);
    }
}
