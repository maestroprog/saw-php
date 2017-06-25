<?php

namespace Saw\Thread\Runner;

use Esockets\Client;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadKnow;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\WorkerThreadPool;
use Saw\Thread\ThreadWithCode;

final class WorkerThreadRunner implements ThreadRunnerInterface
{
    private $client;
    private $commandDispatcher;

    private $threadPool;
    private $runThreadPool;

    public function __construct(Client $client, CommandDispatcher $commandDispatcher)
    {
        $this->client = $client;
        $this->commandDispatcher = $commandDispatcher;
        $this->threadPool = new WorkerThreadPool();
        $this->runThreadPool = new WorkerThreadPool();

        $this->commandDispatcher
            ->add([
                new CommandHandler(ThreadRun::NAME, ThreadRun::class, function (ThreadRun $context) {
                    $result = $this->threadPool->runThreadById($context->getRunId());
                    // todo
                }),
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

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }

    /**
     * Воркер не должен запусукать потоки из приложения.
     * Метод нужен для совместимости работы приложений из скрипта и на воркерах.
     *
     * @return bool
     */
    public function runThreads(): bool
    {
        ;
    }

    public function setResultByRunId(int $id, $data)
    {
        // TODO: Implement setResultByRunId() method.
    }
}
