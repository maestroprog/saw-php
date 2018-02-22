<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

trait SynchronizationTrait
{
    private $synchronized = false;

    public function synchronized(): void
    {
        $this->synchronized = true;
    }

    public function isSynchronized(): bool
    {
        return $this->synchronized;
    }
}
