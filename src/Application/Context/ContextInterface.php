<?php

namespace Saw\Application\Context;

use Saw\Memory\MemoryInterface;

interface ContextInterface extends MemoryInterface
{
    public function getId(): string;

    /**
     * Обновляет метку времени последнего использования контекста.
     */
    public function update();

    /**
     * Можно ли удалить контекст, если он неиспользуемый.
     * Метка времени последнего использования обновляется в методе @see ContextInterface::update()
     *
     * @return bool
     */
    public function canRemoved(): bool;

    public function __sleep();

    public function __wakeup($dump);
}
