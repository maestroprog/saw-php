<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

interface SynchronizationThreadInterface
{
    public function synchronized(): void;

    public function isSynchronized(): bool;
}
