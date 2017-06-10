<?php

namespace Saw\Thread\Runner;

use Saw\Command\ThreadRun;
use Saw\Connector\WebConnector;
use Saw\Thread\AbstractThread;
use Saw\Thread\ThreadWithCode;

class WebThreadRunner implements ThreadRunnerInterface
{
    private $connector;

    private $threads = [];

    public function __construct(WebConnector $connector)
    {
        $this->connector = $connector;
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        return $this->threads[$threadId] = new ThreadWithCode(++$threadId, $uniqueId, $code);
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }

    public function runThreads(): bool
    {
        $dispatcher = $this->connector->getDispatcher();
        foreach ($this->threads as $thread) {
            $dispatcher->create(ThreadRun::NAME)->run(ThreadRun::serializeTask($thread));
        }
    }

    public function synchronizeOne(AbstractThread $thread)
    {
        // TODO: Implement synchronizeOne() method.
    }

    public function synchronizeThreads(array $threads)
    {
        // TODO: Implement synchronizeThreads() method.
    }

    public function synchronizeAll()
    {
        // TODO: Implement synchronizeAll() method.
    }
}
