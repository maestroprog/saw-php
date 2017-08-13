<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;

class WebThreadRunner implements ThreadRunnerInterface
{
    private $client;
    private $commandDispatcher;

    private $runThreads;

    public function __construct(ControllerConnectorInterface $connector)
    {
        $this->client = $connector->getClient();
        $this->commandDispatcher = $connector->getCommandDispatcher();

        $this->runThreads = new RunnableThreadPool();

        $this->commandDispatcher
            ->addHandlers([
                new CommandHandler(ThreadRun::class),
                /*new CommandHandler(
                    ThreadResult::NAME,
                    ThreadResult::class,
                    function (ThreadResult $context) {
                        $this->setResultByRunId($context->getRunId(), $context->getResult());
                    }
                ),*/
                new CommandHandler(
                    ThreadResult::class, function (ThreadResult $context) {
                    $this->runThreads
                        ->getThreadById($context->getRunId())
                        ->setResult($context->getResult());
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
                $this->commandDispatcher
                    ->create(ThreadRun::NAME, $this->client)
                    ->run(ThreadRun::serializeThread($thread));
            } catch (\Throwable $e) {
                $thread->run();
                var_dump($e->getTraceAsString());
                die($e->getMessage());
            } finally {
//                var_dump($thread->getResult(), $thread->hasResult());
            }
        }
        return true;
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->runThreads;
    }
}
