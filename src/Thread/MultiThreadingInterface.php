<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 01.03.2017
 * Time: 0:10
 */

namespace maestroprog\saw\Thread;


interface MultiThreadingInterface
{
    /**
     * Заускает созданные потоки на выполнение.
     * Вернет true, если запуск удался, а false
     * можно интерпретировать как неудачу.
     *
     * @return bool
     */
    public function runThreads(): bool;

    /**
     * Создает новый поток с уникальным идентификатором,
     * и заданным колбеком.
     *
     * @param string $uniqueId
     * @param callable $code
     * @return Thread
     */
    public function thread(string $uniqueId, callable $code): Thread;

    /**
     * Так же создает новый поток, дополнительно принимая
     * список аргументов для колбека.
     *
     * @param string $uniqueId
     * @param callable $code
     * @param array $arguments
     * @return Thread
     */
    public function threadArguments(string $uniqueId, callable $code, array $arguments): Thread;

    /**
     * Дожидается завершения выполнения указанного потока.
     *
     * Если внутри выполняющегося потока вызывать этот метод,
     * указав этот же поток, то произойдет таймаут ожидания.
     * В общем случае так делать не рекомендуется.
     *
     * @param Thread $thread
     * @return void
     */
    public function synchronizeOne(Thread $thread);

    /**
     * Выполняет синхронизацию указанных потоков.
     *
     * @param Thread[] $threads
     * @return void
     * @throws SynchronizeException
     * @throws \InvalidArgumentException Если указан несуществующий поток,
     *          или элемент массива - вовсе не @see Thread
     */
    public function synchronizeThreads(array $threads);

    /**
     * Выполняет синхронизацию всех работающих в данный
     * момент времени потоков.
     *
     * @return void
     * @throws SynchronizeException
     */
    public function synchronizeAll();
}