<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2016
 * Time: 14:50
 */

namespace maestroprog\saw\library;

/**
 * Абстрактный класс приложения.
 * Необходимо наследоваться от него, и запускать приложение.
 */
abstract class Application
{
    /**
     * Инициализирует окружение.
     *
     * @param array $_SERVER
     * @return mixed
     */
    abstract public function init(array $_SERVER);

    /**
     * Запускает выполнение кода.
     * Внутри функции обязательно должны идти вызовы @see \maestroprog\saw\library\TaskManager::run();
     *
     * @param TaskManager $taskManager
     * @return mixed
     */
    abstract public function run(TaskManager $taskManager);

    /**
     * Завершает выполнение приложения.
     * Сбрасывает окружение.
     *
     * @return mixed
     */
    abstract public function end();
}
