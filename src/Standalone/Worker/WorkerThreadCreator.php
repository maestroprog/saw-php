<?php

namespace Maestroprog\Saw\Standalone\Worker;

use Esockets\Client;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\ThreadKnow;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Thread\Creator\ThreadCreator;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\ThreadWithCode;

final class WorkerThreadCreator extends ThreadCreator
{
    private $commander;
    private $client;

    public function __construct(
        ContainerOfThreadPools $pools,
        ApplicationContainer $container,
        Commander $commander,
        Client $client
    )
    {
        parent::__construct($pools, $container);

        $this->commander = $commander;
        $this->client = $client;
    }

    public function thread(string $uniqueId, callable $code): ThreadWithCode
    {
        $threadPool = $this->pools->getCurrentPool();
        if (!$threadPool->exists($uniqueId)) {
            $thread = parent::thread($uniqueId, $code);
            $knowCommand = new ThreadKnow($this->client, $thread->getApplicationId(), $thread->getUniqueId());
            $knowCommand
                ->onError(function () {
                    throw new \RuntimeException('Cannot notify controller.');
                });
            $this->commander->runAsync($knowCommand);
        } else {
            $thread = $threadPool->getThreadById($uniqueId);

            if (!$thread instanceof ThreadWithCode) {
                throw new \InvalidArgumentException('Unknown thread type, ThreadWithCode expected, "%s" given.');
            }
        }

        return $thread;
    }
}
