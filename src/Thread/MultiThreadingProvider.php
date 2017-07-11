<?php

namespace Saw\Thread;

use Saw\Thread\Creator\ThreadCreatorInterface;
use Saw\Thread\Pool\ContainerOfThreadPools;
use Saw\Thread\Runner\ThreadRunnerInterface;
use Saw\Thread\Synchronizer\SynchronizerInterface;

final class MultiThreadingProvider
{
    private $threadPools;
    private $threadCreator;
    private $threadRunner;
    private $synchronizer;

    public function __construct(
        ContainerOfThreadPools $threadPools,
        ThreadCreatorInterface $threadCreator,
        ThreadRunnerInterface $threadRunner,
        SynchronizerInterface $synchronizer
    )
    {
        $this->threadPools = $threadPools;
        $this->threadCreator = $threadCreator;
        $this->threadRunner = $threadRunner;
        $this->synchronizer = $synchronizer;
    }

    public function getThreadPools(): ContainerOfThreadPools
    {
        return $this->threadPools;
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
