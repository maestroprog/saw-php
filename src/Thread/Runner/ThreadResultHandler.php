<?php

namespace Saw\Thread\Runner;

use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Service\CommandDispatcher;

class ThreadResultHandler
{
    private $threadRunner;
    private $commandDispatcher;

    public function __construct(ThreadRunnerInterface $threadRunner, CommandDispatcher $commandDispatcher)
    {
        $this->threadRunner = $threadRunner;
        $this->commandDispatcher = $commandDispatcher;

        $this->commandDispatcher->add([
            new CommandHandler(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    $this->threadRunner
                        ->getRunPool()
                        ->getThreadById($context->getRunId())
                        ->setResult($context->getResult());
                }
            ),
        ]);
    }
}
