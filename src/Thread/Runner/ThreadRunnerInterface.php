<?php

namespace Saw\Thread\Runner;

use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;

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

    public function getRunPool(): AbstractThreadPool;

    public function setResultByRunId(int $id, $data);
}
