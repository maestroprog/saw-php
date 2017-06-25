<?php

namespace Saw\Thread\Creator;

use Esockets\Client;
use Saw\Command\ThreadKnow;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\ThreadWithCode;

class WorkerThreadCreator extends ThreadCreator
{
    private $commandDispatcher;
    private $client;

    public function __construct(CommandDispatcher $commandDispatcher, Client $client)
    {
        parent::__construct();
        $this->commandDispatcher = $commandDispatcher;
        $this->client = $client;

        $this->commandDispatcher
            ->add([
            ]);
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        if (!$this->threadPool->existsThreadByUniqueId($uniqueId)) {
            $thread = new ThreadWithCode(++$threadId, $uniqueId, $code);
            $this->threadPool->add($thread);
            $this->commandDispatcher->create(ThreadKnow::NAME, $this->client)
                ->onError(function () {
                    throw new \RuntimeException('Cannot notify controller.');
                })
                ->run(['unique_id' => $thread->getUniqueId()]);
        } else {
            $thread = $this->threadPool->getThreadByUniqueId($uniqueId);
        }
        return $thread;
    }
}
