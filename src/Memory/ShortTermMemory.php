<?php

namespace Maestroprog\Saw\Memory;

final class ShortTermMemory extends CacheMemory implements ShareableMemoryInterface
{
    /**
     * @var ShareableMemoryInterface
     */
    protected $memory;
    private $prefix;

    public function __construct(ShareableMemoryInterface $shareableMemory, string $uniquePrefix)
    {
        parent::__construct($shareableMemory);
        $this->prefix = $uniquePrefix;
    }

    protected function prefix(): string
    {
        return parent::prefix(); // todo WTF?
        return $this->prefix;
    }

    public function share(string $varName)
    {
        $this->memory->share($varName);
    }

    public function request(string $varName)
    {
        return $this->memory->request($varName);
    }
}
