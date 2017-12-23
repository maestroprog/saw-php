<?php

namespace Maestroprog\Saw\Application\Context;

use Maestroprog\Saw\Memory\MemoryInterface;

final class ContextPool
{
    private $storage;

    public function __construct(MemoryInterface $memory)
    {
    }

    /**
     * Добавляет новый контекст в пул.
     *
     * @param ContextInterface $context
     */
    public function add(ContextInterface $context)
    {

    }

    public function switchTo(string $contextId)
    {

    }

    public function current(): ContextInterface
    {

    }

    /**
     * Удаляет неиспользуемые контексты из пула.
     */
    public function removeOld()
    {

    }
}
