<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;
use Maestroprog\Saw\ValueObject\SawEnv;

class AsyncSynchronizer implements SynchronizerInterface
{
    private $threadRunner;
    private $syncGenerator;
    private $generatorExec;
    private $init = false;
    private $env;

    public function __construct(ThreadRunnerInterface $threadRunner, \Generator $syncGenerator, SawEnv $env)
    {
        $this->threadRunner = $threadRunner;
        $this->syncGenerator = $syncGenerator;
        $this->env = $env;
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        do {
            /* Генератор ThreadRunner-а
             * Выполняет асинхронный код потоков и рекурсивных синхронизаторов. */

            $synchronized = true;
            foreach ($threads as $thread) {
                $synchronized = $synchronized && $thread->isSynchronized();
                if (!$synchronized) {
                    break;
                }
            }
            if (!$synchronized) {

                if (!$this->init) {
                    $this->init = true;
                    $this->generatorExec = true;
                    $this->syncGenerator->rewind();
                    $this->generatorExec = false;
                }
                if (!$this->generatorExec || $this->env->isWorker()) {
                    if (!$this->syncGenerator->valid()) {
                        throw new \LogicException('Logic invalid.');
                    }
                    $this->generatorExec = true;
                    yield $this->syncGenerator->current();
                    $this->syncGenerator->next();
                    $this->generatorExec = false;
                } else {
                    yield;// AsyncBus::SIGNAL_PAUSE;
                }
            }
        } while (!$synchronized);
    }

    public function synchronizeAll(): \Generator
    {
        yield from $this->synchronizeThreads(...$this->threadRunner->getThreadPool()->getThreads());
    }
}
