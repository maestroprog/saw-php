<?php

namespace Saw\Thread\Runner;

use Esockets\Client;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\RunnableThreadPool;

class WebThreadRunner implements ThreadRunnerInterface
{
    private $client;
    private $commandDispatcher;

    private $runThreads;

    public function __construct(Client $client, CommandDispatcher $commandDispatcher)
    {
        $this->client = $client;
        $this->commandDispatcher = $commandDispatcher;

        $this->runThreads = new RunnableThreadPool();

        $this->commandDispatcher
            ->add([
                new CommandHandler(ThreadRun::NAME, ThreadRun::class),
                new CommandHandler(
                    ThreadResult::NAME,
                    ThreadResult::class,
                    function (ThreadResult $context) {
                        $this->setResultByRunId($context->getRunId(), $context->getResult());
                    }
                ),
            ]);
    }

    /**
     * @param AbstractThread[] $threads
     * @return bool
     */
    public function runThreads(array $threads): bool
    {
        foreach ($threads as $thread) {
            $this->runThreads->add($thread);
            try {
                $this->commandDispatcher->create(ThreadRun::NAME, $this->client)
                    ->run(ThreadRun::serializeTask($thread));
            } catch (\Exception $e) {
                $thread->run();
            }
        }
        return true;
    }

    public function setResultByRunId(int $id, $data)
    {

    }
}
