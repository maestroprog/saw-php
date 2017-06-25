<?php

namespace Saw\Thread\Synchronizer;


use Saw\Thread\AbstractThread;

class WebThreadSynchronizer implements SynchronizerInterface
{
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
            foreach ($this->threads as $thread) {
                $synchronizeOk = $synchronizeOk && $thread->hasResult();
                if (!$synchronizeOk) break;
            }
            if ($synchronizeOk) {
                $synchronized = true;
            }
        } while (!$synchronized);
    }
}