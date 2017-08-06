<?php

namespace Maestroprog\Saw\Standalone\Worker;

use Esockets\Client;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\PoolOfUniqueThreads;
use Maestroprog\Saw\Thread\Runner\ThreadRunnerDisablingSupportInterface;

final class WorkerThreadRunner implements ThreadRunnerDisablingSupportInterface
{
    private $client;
    private $commandDispatcher;
    private $applicationContainer;

    private $threadPool;
    private $runThreadPool;
    private $disabled = true;

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
                    ThreadResult::class, function (ThreadResult $context) {
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
     * Воркер не должен запускать потоки из приложения.
     * Метод нужен для совместимости работы приложений из скрипта и на воркерах.
     *
     * @param AbstractThread[] $threads
     * @return bool
     */
    public function runThreads(array $threads): bool
    {
        if (!$this->disabled) {
            foreach ($threads as $thread) {
                $this->runThreadPool->add($thread);
                try {
                    $this->commandDispatcher
                        ->create(ThreadRun::NAME, $this->client)
                        ->run(ThreadRun::serializeThread($thread));
                } catch (\Throwable $e) {
                    $thread->run();
                    var_dump($e->getTraceAsString());
                    die($e->getMessage());
                } finally {
                    var_dump($thread->getResult(), $thread->hasResult());
                }
            }
        } else {
            $this->enable();
        }
        return true;
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->runThreadPool;
    }

    public function setResultByRunId(int $id, $data)
    {
        $this->runThreadPool->getThreadById($id)->setResult($data);
    }

    public function disable()
    {
        $this->disabled = true;
    }

    public function enable()
    {
        $this->disabled = false;
    }
}
