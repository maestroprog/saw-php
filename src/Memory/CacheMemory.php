<?php

namespace Maestroprog\Saw\Memory;

abstract class CacheMemory implements MemoryInterface
{
    use PrefixMemoryTrait;

    const VAR_LIFETIME = 3600;

    protected $memory;

    public function __construct(MemoryInterface $storage)
    {
        // todo auto clean
        $this->memory = $storage;
    }

    public function has(string $varName): bool
    {
        if ($thereIs = $this->memory->has($this->pfx($varName))) {
            $this->access($varName);
        }
        return $thereIs;
    }

    public function read(string $varName)
    {
        $value = $this->memory->read($this->pfx($varName));
        $this->access($varName);
        return $value;
    }

    public function write(string $varName, $variable): bool
    {
        return $this->memory->write($this->pfx($varName), $variable);
    }

    public function remove(string $varName)
    {
        $this->memory->remove($this->pfx($varName));
    }

    public function list(string $prefix = null): array
    {
        return $this->memory->list($prefix);
    }

    public function free()
    {
        $this->memory->free();
    }

    protected function access(string $varName)
    {
        $this->memory->write('lifetime.' . $this->pfx($varName), time());
    }

    private function pfx(string $varName): string
    {
        return $this->prefix() . '.' . $varName;
    }
}
