<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

interface SynchronizerInterface
{
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
     * Выполняет синхронизацию всех запущенных в данный
     * момент времени потоков.
     *
     * @return \Generator
     * @throws SynchronizeException
     */
    public function synchronizeAll(): \Generator;
}
