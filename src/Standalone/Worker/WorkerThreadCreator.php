<?php

namespace Maestroprog\Saw\Standalone\Worker;

use Esockets\Client;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadKnow;
use Maestroprog\Saw\Saw;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\Creator\ThreadCreator;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\ThreadWithCode;

final class WorkerThreadCreator extends ThreadCreator
{
    private $commander;
    private $client;

    public function __construct(
        ContainerOfThreadPools $pools,
        Commander $commander,
        Client $client
    )
    {
        parent::__construct($pools);
        $this->commander = $commander;
        $this->client = $client;
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        $threadPool = $this->pools->getCurrentPool();
        if (!$threadPool->exists($uniqueId)) {
            $thread = new ThreadWithCode(++$threadId, Saw::getCurrentApp()->getId(), $uniqueId, $code);
            $threadPool->add($thread);
            $this
                ->commander
                ->runAsync((new ThreadKnow($this->client, $thread->getApplicationId(), $thread->getUniqueId()))
                    ->onError(function () {
                        throw new \RuntimeException('Cannot notify controller.');
                    }));
        } else {
            $thread = $threadPool->getThreadById($uniqueId);
        }

        return $thread;
    }
}
