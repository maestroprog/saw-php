<?php

namespace Saw\Application\Context;

interface ContextInterface
{
    /**
     * Обновляет метку времени последнего использования контекста.
     */
    public function update();

    /**
     * Можно ли удалить контекст, если он неиспользуемый.
     *
     * @return bool
     */
    public function canRemoved(): bool;
}
