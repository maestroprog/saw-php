<?php

namespace Maestroprog\Saw\Memory;

final class LongTermMemory extends CacheMemory
{
    const VAR_LIFETIME = 3600 * 24 * 7; // 1 week

    private $prefix;

    public function __construct(MemoryInterface $storage, string $uniquePrefix)
    {
        parent::__construct($storage);
        $this->prefix = $uniquePrefix;
    }

    protected function prefix(): string
    {
        return $this->prefix;
    }
}
