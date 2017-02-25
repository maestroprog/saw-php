<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2016
 * Time: 14:50
 */

namespace maestroprog\saw\Heading;

use maestroprog\saw\entity\Task;

/**
 * Абстрактный класс приложения.
 * Необходимо наследоваться от него, и запускать приложение.
 */
abstract class Application
{
    protected $taskManager;

    /**
     * Application constructor.
     * @param TaskManager $taskManager
     */
    final public function __construct(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
    }

    /**
     * Инициализирует окружение.
     *
     * @return mixed
     */
    public function init()
    {

    }

    /**
     * Запускает выполнение кода.
     * Внутри функции обязательно должны идти вызовы @see \maestroprog\saw\Heading\TaskManager::run();
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * Завершает выполнение приложения.
     * Сбрасывает окружение.
     *
     * @return mixed
     */
    abstract public function end();

    final public function thread(callable $callback, string $name): Task
    {
        return $this->taskManager->run($callback, $name);
    }
}
