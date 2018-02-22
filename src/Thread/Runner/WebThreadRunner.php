<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadBroadcast;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;

class WebThreadRunner implements ThreadRunnerInterface
{
    private $client;
    private $commandDispatcher;
    private $commander;
    private $runThreads;

    public function __construct(ControllerConnectorInterface $connector, Commander $commander)
    {
        $this->client = $connector->getClient();
        $this->commandDispatcher = $connector->getCommandDispatcher();
        $this->commander = $commander;

        $this->runThreads = new RunnableThreadPool();

        $this->commandDispatcher
            ->addHandlers([
                new CommandHandler(ThreadResult::class, function (ThreadResult $context) {
                    $this
                        ->runThreads
                        ->getThreadById($context->getRunId())
                        ->setResult($context->getResult());
                }),
            ]);
    }

    /**
     * @param AbstractThread[] $threads
     *
     * @return bool
     */
    public function runThreads(AbstractThread ...$threads): bool
    {
//        foreach ($threads as $thread) {
//            $this->runThreads->add($thread);
//            try {
//                $this
//                    ->commander
//                    ->runAsync(new ThreadRun(
//                        $this->client,
//                        $thread->getId(),
//                        $thread->getApplicationId(),
//                        $thread->getUniqueId(),
//                        $thread->getArguments()
//                    ));
//            } catch (\Throwable $e) {
//                $thread->run();
//                var_dump($e->getTraceAsString());
//                die($e->getMessage());
//            }
//        }
        $commands = [];
        foreach ($threads as $thread) {
            $this->runThreads->add($thread);
            $commands[] = new ThreadRun(
                $this->client,
                $thread->getId(),
                $thread->getApplicationId(),
                $thread->getUniqueId(),
                $thread->getArguments()
            );
        }
        try {
            $this
                ->commander
                ->runPacket(...$commands);
        } catch (\Throwable $e) {
//            $thread->run(); todo run really if not run into saw?
            die($e->getMessage());
        }

        return true;
    }

    public function broadcastThreads(BroadcastThread ...$threads): bool
    {
        $result = false;

        foreach ($threads as $thread) {
            $this->runThreads->add($thread);
            try {
                $this
                    ->commander
                    ->runAsync(new ThreadBroadcast(
                        $this->client,
                        $thread->getId(),
                        $thread->getApplicationId(),
                        $thread->getUniqueId(),
                        $thread->getArguments()
                    ));
                $result = true;
            } catch (\Throwable $e) {
                try {
                    $thread->run();
                    $result = true;
                } catch (\Throwable $e) {
                    var_dump($e->getTraceAsString());
                }
            }
        }

        return $result;
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->runThreads;
    }
}
