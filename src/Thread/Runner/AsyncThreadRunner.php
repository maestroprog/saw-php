<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Standalone\Controller\CycleInterface;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;
use Maestroprog\Saw\Thread\ThreadWithCode;

class AsyncThreadRunner implements ThreadRunnerInterface, CycleInterface
{
    /**
     * @var RunnableThreadPool|AbstractThread[]
     */
    protected $threadPool;
    protected $generators;
    protected $queue;

    public function __construct()
    {
        $this->threadPool = new RunnableThreadPool();
        $this->generators = new \SplObjectStorage();
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->threadPool;
    }

    public function runThreads(AbstractThread ...$threads): bool
    {
        foreach ($threads as $thread) {
            if (!$thread instanceof ThreadWithCode) {
                throw new \InvalidArgumentException('ThreadWithCode expected, ' . get_class($thread) . ' given.');
            }

            $this->threadPool->add($thread);
        }

        return true;
    }

    public function broadcastThreads(BroadcastThread ...$threads): bool
    {
        return $this->runThreads(...$threads);
    }

    public function work(): \Generator
    {
        while (true) {
            do {
                $workThreads = 0;

                foreach ($this->threadPool as $thread) {
                    if ($thread->hasResult()) {
                        continue;
                    }
                    $rewound = false;
                    /** @var \Generator $generator */
                    if (!$this->generators->contains($thread)) {
                        $generator = $thread->run();
                        $generator->rewind();
                        $rewound = true;
                        $this->generators->attach($thread, $generator);
                    } else {
                        $generator = $this->generators[$thread];
                    }
                    yield __METHOD__ . '.' . $generator->current();
                    if ($generator->valid()) {
                        if (!$rewound) {
                            $generator->next();
                        }
                        $workThreads++;
                    }
                    if (!$generator->valid()) {
                        $thread->setResult($generator->getReturn());
                        $this->generators->detach($thread);
                        $this->threadPool->remove($thread);
                    }
                }

            } while ($workThreads > 0);

            yield __METHOD__;
        }
    }
}
