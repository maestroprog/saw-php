<?php

namespace Saw\Thread;

use Saw\Thread\Creator\ThreadCreatorInterface;
use Saw\Thread\Runner\ThreadRunnerInterface;
use Saw\Thread\Synchronizer\SynchronizerInterface;

final class MultiThreadingProvider
{
    private $threadCreator;
    private $threadRunner;
    private $synchronizer;

    public function __construct(
        ThreadCreatorInterface $threadCreator,
        ThreadRunnerInterface $threadRunner,
        SynchronizerInterface $synchronizer
    )
    {
        $this->threadCreator = $threadCreator;
        $this->threadRunner = $threadRunner;
        $this->synchronizer = $synchronizer;
    }

    public function getThreadCreator(): ThreadCreatorInterface
    {
        return $this->threadCreator;
    }

    public function getThreadRunner(): ThreadRunnerInterface
    {
        return $this->threadRunner;
    }

    public function getSynchronizer(): SynchronizerInterface
    {
        return $this->synchronizer;
    }
}
