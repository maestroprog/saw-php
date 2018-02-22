<?php

namespace Maestroprog\Saw\Standalone\Controller;

/**
 * Интерфейс, подразумевающий циклическую работу.
 */
interface CycleInterface
{
    public function work(): \Generator;
}
