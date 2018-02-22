<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Service\CommandDispatcher;

/** @deprecated */
class ThreadResultHandler
{
    private $threadRunner;
    private $commandDispatcher;

    public function __construct(ThreadRunnerInterface $threadRunner, CommandDispatcher $commandDispatcher)
    {
        $this->threadRunner = $threadRunner;
        $this->commandDispatcher = $commandDispatcher;

        $this->commandDispatcher->addHandlers([
        ]);
    }
}
