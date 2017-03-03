<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 25.02.2017
 * Time: 15:28
 */

namespace maestroprog\saw\Application;


use maestroprog\saw\Dto\Result;
use maestroprog\saw\Thread\MultiThreadingInterface;
use maestroprog\saw\Thread\Thread;

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
     * для выполнения нового запроса.
     *
     * Метод не выполняется на воркерах.
     *
     * @return mixed
     */
    public function init();

    /**
     * Описывает основной поток выполнения приложения.
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