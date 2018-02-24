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

            $generator = $thread->run();
            $this->generators->attach($thread, $generator);
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
                    /** @var \Generator $generator */
                    $generator = $this->generators[$thread];
                    /* Генератор, выполняющий асинхронный код потока. */
                    if ($generator->valid()) {
                        yield $generator->current();
                        $generator->next();
                        if ($generator->valid()) {
                            $workThreads++;
                        } else {
                            $thread->setResult($generator->getReturn());
                            $this->threadPool->remove($thread);
                            $this->generators->detach($thread);
                        }
                    }
                }

            } while ($workThreads > 0);

            yield __CLASS__ . '::' . __FUNCTION__;
        }
    }
}
