<?php

namespace Saw\Thread\Synchronizer;


use Esockets\Client;
use Saw\Thread\AbstractThread;
use Saw\Thread\Runner\ThreadRunnerInterface;

class WebThreadSynchronizer implements SynchronizerInterface
{
    private $threadRunner;
    private $client;

    public function __construct(ThreadRunnerInterface $threadRunner, Client $client)
    {
        $this->threadRunner = $threadRunner;
        $this->client = $client;
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
            foreach ($this->threadRunner->getThreadPool() as $thread) {
                $synchronizeOk = $synchronizeOk && $thread->hasResult();
                if (!$synchronizeOk) break;
            }
            if ($synchronizeOk) {
                $synchronized = true;
            }
        } while (!$synchronized);
    }
}