<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

interface SynchronizerInterface
{
    /**
     * Дожидается завершения выполнения указанного потока.
     *
     * Если внутри выполняющегося потока вызывать этот метод,
     * указав этот же поток, то произойдет таймаут ожидания.
     * В общем случае так делать не рекомендуется.
     *
     * @param SynchronizationThreadInterface $thread
     *
     * @return \Generator
     */
    public function synchronizeOne(SynchronizationThreadInterface $thread): \Generator;

    /**
     * Выполняет синхронизацию указанных потоков.
     *
     * @param SynchronizationThreadInterface[] $threads
     *
     * @return \Generator
     * @throws SynchronizeException
     * @throws \InvalidArgumentException Если указан несуществующий поток,
     *          или элемент массива - вовсе не @see Thread
     */
    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator;

    /**
     * Выполняет синхронизацию всех работающих в данный
     * момент времени потоков.
     *
     * @return \Generator
     * @throws SynchronizeException
     */
    public function synchronizeAll(): \Generator;
}
