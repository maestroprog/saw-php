<?php

namespace Saw\Application;

use Saw\Application\Context\ContextInterface;

interface ApplicationInterface
{
    /**
     * Возвращает уникальный идентификатор приложения.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Вернёт контекст текущего запроса.
     * Контексты запросов аналогичны сессиям.
     *
     * @return ContextInterface
     */
    public function context(): ContextInterface;

    /**
     * Выполняется, после получения нового запроса.
     * Метод должен выполнять инициализацию приложения
     * - загружать все необходимые данные
     * для дальнейшей обработки нового запроса.
     *
     * Метод не выполняется на воркерах.
     *
     * @return void
     */
    public function init();

    /**
     * Запускает работу приложения.
     *
     * @return void
     */
    public function run();

    /**
     * Вызывается после завершения работы всех потоков.
     *
     * Собирает результаты выполнения потоков.
     * На основе полученных результатов конструирует общий
     * результат выполнения приложения, и возвращает его.
     *
     * @return void
     */
    public function end();
}
