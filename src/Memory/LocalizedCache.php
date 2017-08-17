<?php

namespace Maestroprog\Saw\Memory;

final class LocalizedCache extends CacheMemory implements ShareableMemoryInterface
{
    /**
     * @var ShareableMemoryInterface
     */
    protected $storage;

    public function __construct(ShareableMemoryInterface $shareableMemory)
    {
        parent::__construct($shareableMemory);
    }

    public function list(string $prefix = null): array
    {
        return parent::list($prefix);
    }

    protected function prefix(): string
    {
        return parent::prefix(); // todo WTF?
    }

    public function share(string $varName)
    {
        $this->storage->share($varName);
    }

    public function request(string $varName)
    {
        return $this->storage->request($varName);
    }
}
