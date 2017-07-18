<?php

namespace Saw\Thread\Runner;

use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Connector\ControllerConnectorInterface;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;
use Saw\Thread\Pool\RunnableThreadPool;

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
            ->add([
                new CommandHandler(ThreadRun::NAME, ThreadRun::class),
                /*new CommandHandler(
                    ThreadResult::NAME,
                    ThreadResult::class,
                    function (ThreadResult $context) {
                        $this->setResultByRunId($context->getRunId(), $context->getResult());
                    }
                ),*/
                new CommandHandler(
                    ThreadResult::NAME,
                    ThreadResult::class,
                    function (ThreadResult $context) {
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
