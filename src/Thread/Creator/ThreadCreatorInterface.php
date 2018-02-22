<?php

namespace Maestroprog\Saw\Thread\Creator;

use Maestroprog\Saw\Thread\ThreadWithCode;

interface ThreadCreatorInterface
{
    /**
     * Создает новый поток с уникальным идентификатором,
     * и заданным колбеком.
     *
     * @param string $uniqueId
     * @param callable $code
     *
     * @return ThreadWithCode
     */
    public function thread(string $uniqueId, callable $code): ThreadWithCode;

    /**
     * Так же создает новый поток, дополнительно принимая
     * список аргументов для колбека.
     *
     * @param string $uniqueId
     * @param callable $code
     * @param array $arguments
     *
     * @return ThreadWithCode
     */
    public function threadArguments(string $uniqueId, callable $code, array $arguments): ThreadWithCode;
}
