<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 25.02.2017
 * Time: 15:28
 */

namespace maestroprog\saw\Application;


use maestroprog\saw\Thread\Thread;

interface Application
{

    public function __construct(string $id, array $config);

    public function getId(): string;

    public function init();

    /**
     * Описывает основной поток выполнения приложения.
     *
     * @return mixed
     */
    public function main();

    /**
     * @return mixed
     */
    public function run();

    /**
     * Вызывается после завершения выполнения всех потоков.
     *
     * Собирает результаты выполнения потоков.
     * На основе полученных результатов конструирует
     * общий результат выполнения приложения, и возвращает его.
     *
     * @return
     */
    public function end();

    /**
     * Создает новый поток с уникальным идентификатором, и заданным кодом.
     *
     * @param string $uniqueId
     * @param \Closure $code
     * @return Thread
     */
    public function thread(string $uniqueId, \Closure $code): Thread;
}