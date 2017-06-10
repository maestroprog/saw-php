<?php

namespace Saw\Application\Context;

final class ContextPool
{
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

    /**
     * Удаляет неиспользуемые контексты из пула.
     */
    public function removeOld()
    {

    }
}
