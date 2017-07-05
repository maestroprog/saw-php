<?php

namespace Saw\Standalone\Worker;

use Esockets\Client;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadKnow;
use Saw\Saw;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\Creator\ThreadCreator;
use Saw\Thread\Pool\ContainerOfThreadPools;
use Saw\Thread\ThreadWithCode;

final class WorkerThreadCreator extends ThreadCreator
{
    private $commandDispatcher;
    private $client;

    public function __construct(ContainerOfThreadPools $pools, CommandDispatcher $commandDispatcher, Client $client)
    {
        parent::__construct($pools);
        $this->commandDispatcher = $commandDispatcher;
        $this->client = $client;

        $this->commandDispatcher
            ->add([
                new CommandHandler(ThreadKnow::NAME, ThreadKnow::class),
            ]);
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        $threadPool = $this->pools->getCurrentPool();
        if (!$threadPool->exists($uniqueId)) {
            $thread = new ThreadWithCode(++$threadId, Saw::getCurrentApp()->getId(), $uniqueId, $code);
            $threadPool->add($thread);
            $this->commandDispatcher->create(ThreadKnow::NAME, $this->client)
                ->onError(function () {
                    throw new \RuntimeException('Cannot notify controller.');
                })
                ->run(['unique_id' => $thread->getUniqueId(), 'application_id' => $thread->getApplicationId()]);
        } else {
            $thread = $threadPool->getThreadById($uniqueId);
        }
        return $thread;
    }
}