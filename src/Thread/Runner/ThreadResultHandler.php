<?php

namespace Saw\Thread\Runner;

use Saw\Service\CommandDispatcher;

// todo remove
class ThreadResultHandler
{
    private $threadRunner;
    private $commandDispatcher;

    public function __construct(ThreadRunnerInterface $threadRunner, CommandDispatcher $commandDispatcher)
    {
        $this->threadRunner = $threadRunner;
        $this->commandDispatcher = $commandDispatcher;

        $this->commandDispatcher->add([
        ]);
    }
}
