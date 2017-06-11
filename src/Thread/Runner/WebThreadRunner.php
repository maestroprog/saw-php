<?php

namespace Saw\Thread\Runner;

use Esockets\Client;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Service\CommandDispatcher;
use Saw\Thread\AbstractThread;
use Saw\Thread\ThreadWithCode;

class WebThreadRunner implements ThreadRunnerInterface
{
    private $client;
    private $commandDispatcher;

    private $threads = [];

    public function __construct(Client $client, CommandDispatcher $commandDispatcher)
    {
        $this->client = $client;
        $this->commandDispatcher = $commandDispatcher;

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

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        return $this->threads[$threadId] = new ThreadWithCode(++$threadId, $uniqueId, $code);
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }

    public function runThreads(): bool
    {
        foreach ($this->threads as $thread) {
            $this->commandDispatcher->create(ThreadRun::NAME, $this->client)
                ->run(ThreadRun::serializeTask($thread));
        }
    }

    public function synchronizeOne(AbstractThread $thread)
    {
        while (!$thread->hasResult()) {
            $this->client->live();
        }
    }

    /**
     * @inheritdoc
     */
    public function synchronizeThreads(array $threads)
    {
        $synchronized = false;
        do {
            $this->client->live();
            $synchronizeOk = true;
            /**
             * @var $thread AbstractThread
             */
            foreach ($threads as $thread) {
                $synchronizeOk = $synchronizeOk && $thread->hasResult();
                if (!$synchronizeOk) break;
            }
            if ($synchronizeOk) {
                $synchronized = true;
            }
        } while (!$synchronized);
    }

    public function synchronizeAll()
    {
        $synchronized = false;
        do {
            $this->client->live();
            $synchronizeOk = true;
            foreach ($this->threads as $thread) {
                $synchronizeOk = $synchronizeOk && $thread->hasResult();
                if (!$synchronizeOk) break;
            }
            if ($synchronizeOk) {
                $synchronized = true;
            }
        } while (!$synchronized);
    }

    public function setResultByRunId(int $id, $data)
    {

    }
}
