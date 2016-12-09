<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:14
 */

namespace maestroprog\saw\library;

use maestroprog\saw\service\Init;
use maestroprog\saw\service\Worker;

/**
 * Задача для воркера.
 * Представляет из себя изолированный от внешнего окружения объект.
 * @version 0.1-dev
 * Описание относится именно к данной версии.
 * @todo Нужно окружение.
 */
class Task extends Singleton
{
    protected static $instance;
    /**
     * @var Worker|Init
     */
    protected $controller;

    public function setController(Worker $controller)
    {
        if (!($controller instanceof Worker)) {
            // todo выпилить
            throw new \Exception('Cannot set controller');
        }
        $this->controller = $controller;
        return $this;
    }

    public function run(callable $task, string $name, &$result = null)
    {
        if ($this->controller->addTask($task, $name, $result)) {
            // можно спокойно выходить отсюда :)
        } else {
            $result = $task();
        }
    }

    public function sync(array $names)
    {
        return $this->controller->syncTask($names);
    }
}
