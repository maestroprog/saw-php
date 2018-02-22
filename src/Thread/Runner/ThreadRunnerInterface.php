<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;

interface ThreadRunnerInterface
{
    /**
     * Заускает созданные потоки на выполнение.
     * Вернет true, если запуск удался, а false
     * можно интерпретировать как неудачу.
     *
     * @param AbstractThread[] $threads
     * @return bool
     */
    public function runThreads(array $threads): bool;

    /**
     * @param AbstractThread[] ...$threads
     * @return bool
     */
    public function broadcastThreads(AbstractThread ...$threads): bool;

    public function getThreadPool(): AbstractThreadPool;
}
