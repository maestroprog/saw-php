<?php

namespace Saw\Thread\Synchronizer;

use Saw\Thread\AbstractThread;

interface SynchronizerInterface
{
    /**
     * Дожидается завершения выполнения указанного потока.
     *
     * Если внутри выполняющегося потока вызывать этот метод,
     * указав этот же поток, то произойдет таймаут ожидания.
     * В общем случае так делать не рекомендуется.
     *
     * @param AbstractThread $thread
     * @return void
     */
    public function synchronizeOne(AbstractThread $thread);

    /**
     * Выполняет синхронизацию указанных потоков.
     *
     * @param AbstractThread[] $threads
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
