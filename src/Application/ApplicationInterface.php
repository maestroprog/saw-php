<?php

namespace Saw\Application;

use Saw\Dto\Result;
use Saw\Thread\MultiThreadingInterface;

interface ApplicationInterface extends MultiThreadingInterface
{
    /**
     * Загружает приложение с заданным уникальным
     * идентификатором, и конфигом.
     *
     * @param string $id
     * @param array $config
     */
    public function __construct(string $id, array $config);

    /**
     * Возвращает уникальный идентификатор приложения.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Выполняется, после получения нового запроса.
     * Метод должен выполнять инициализацию приложения
     * - загружать все необходимые данные
     * для дальнейшей обработки нового запроса.
     *
     * Метод не выполняется на воркерах.
     *
     * @return mixed
     */
    public function init();

    /**
     * Описывает основной поток выполнения приложения.
     * Этот метод должен содержать запуск остальных потоков приложения.
     *
     * @return mixed
     */
    public function main();

    /**
     * Запускает работу приложения.
     *
     * @return mixed
     */
    public function run();

    /**
     * Вызывается после завершения работы всех потоков.
     *
     * Собирает результаты выполнения потоков.
     * На основе полученных результатов конструирует общий
     * результат выполнения приложения, и возвращает его.
     *
     * @return Result
     */
    public function end(): Result;
}
