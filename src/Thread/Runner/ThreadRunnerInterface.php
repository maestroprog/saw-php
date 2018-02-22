<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;

interface ThreadRunnerInterface
{
    /**
     * Заускает созданные потоки на выполнение.
     * Вернет true, если запуск удался, а false
     * можно интерпретировать как неудачу.
     *
     * @param AbstractThread[] $threads
     *
     * @return bool
     */
    public function runThreads(AbstractThread ...$threads): bool;

    /**
     * @param BroadcastThread[] ...$threads
     *
     * @return bool
     */
    public function broadcastThreads(BroadcastThread ...$threads): bool;

    public function getThreadPool(): AbstractThreadPool;
}
