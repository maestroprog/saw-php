<?php

namespace Saw\Thread\Runner;

use Esockets\Client;
use Saw\Application\ApplicationContainer;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;
use Saw\Thread\Pool\PoolOfUniqueThreads;

final class WorkerThreadRunner implements ThreadRunnerInterface
{
    private $client;
    private $commandDispatcher;
    private $applicationContainer;

    private $threadPool;
    private $runThreadPool;

    public function __construct(
        Client $client,
        CommandDispatcher $commandDispatcher,
        ApplicationContainer $applicationContainer
    )
    {
        $this->client = $client;
        $this->commandDispatcher = $commandDispatcher;
        $this->applicationContainer = $applicationContainer;

        $this->threadPool = new PoolOfUniqueThreads();
        $this->runThreadPool = new PoolOfUniqueThreads();

        $this->commandDispatcher
            ->add([
                new CommandHandler(
                    ThreadResult::NAME,
                    ThreadResult::class,
                    function (ThreadResult $context) {
                        $this->runThreadPool
                            ->getThreadById($context->getRunId())
                            ->setResult($context->getResult());
                    }
                ),
            ]);
    }/*

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

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }*/

    /**
     * Воркер не должен запусукать потоки из приложения.
     * Метод нужен для совместимости работы приложений из скрипта и на воркерах.
     *
     * @param AbstractThread[] $threads
     * @return bool
     */
    public function runThreads(array $threads): bool
    {
        foreach ($threads as $thread) {
//todo
        }
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->runThreadPool;
    }

    public function setResultByRunId(int $id, $data)
    {
        // TODO: Implement setResultByRunId() method.
    }
}
