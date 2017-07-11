<?php

namespace Saw\Thread\Synchronizer;


use Saw\Connector\ControllerConnectorInterface;
use Saw\Thread\AbstractThread;
use Saw\Thread\Runner\ThreadRunnerInterface;

class WebThreadSynchronizer implements SynchronizerInterface
{
    private $threadRunner;
    private $connector;

    public function __construct(ThreadRunnerInterface $threadRunner, ControllerConnectorInterface $connector)
    {
        $this->threadRunner = $threadRunner;
        $this->connector = $connector;
    }

    public function synchronizeOne(AbstractThread $thread)
    {
        while (!$thread->hasResult()) {
            $this->connector->work();
        }
    }

    /**
     * @inheritdoc
     */
    public function synchronizeThreads(array $threads)
    {
        $synchronized = false;
        do {
            $this->connector->work();
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
            $this->connector->work();
            $synchronizeOk = true;
            foreach ($this->threadRunner->getThreadPool() as $thread) {
                /**
                 * @var $thread AbstractThread
                 */
                $synchronizeOk = $synchronizeOk && $thread->hasResult();
                if (!$synchronizeOk) break;
            }
            if ($synchronizeOk) {
                $synchronized = true;
            }
        } while (!$synchronized);
    }
}
