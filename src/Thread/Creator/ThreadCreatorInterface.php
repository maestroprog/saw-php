<?php

namespace Saw\Thread\Creator;

use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;

interface ThreadCreatorInterface
{
    /**
     * Создает новый поток с уникальным идентификатором,
     * и заданным колбеком.
     *
     * @param string $uniqueId
     * @param callable $code
     * @return AbstractThread
     */
    public function thread(string $uniqueId, callable $code): AbstractThread;

    /**
     * Так же создает новый поток, дополнительно принимая
     * список аргументов для колбека.
     *
     * @param string $uniqueId
     * @param callable $code
     * @param array $arguments
     * @return AbstractThread
     */
    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread;

    public function getThreadPool(): AbstractThreadPool;
}
