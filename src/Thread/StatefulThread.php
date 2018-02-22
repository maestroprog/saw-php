<?php

namespace Maestroprog\Saw\Thread;

use Maestroprog\Saw\Thread\Synchronizer\SynchronizationThreadInterface;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizationTrait;

/**
 * Тред с хранением состояния.
 */
abstract class StatefulThread extends AbstractThread implements SynchronizationThreadInterface
{
    use SynchronizationTrait;

    const STATE_NEW = 0; // поток создан
    const STATE_RUN = 1; // поток выполняется
    const STATE_END = 2; // выполнение потока завершено
    const STATE_ERR = 3; // ошибка при выполнении потока

    protected $state = self::STATE_NEW;

    public function getCurrentState(): int
    {
        return $this->state;
    }

    public function hasResult(): bool
    {
        return $this->state > self::STATE_RUN;
    }

    public function setResult($data): void
    {
        parent::setResult($data);

        $this->state = self::STATE_END;
    }

    public function reset(): void
    {
        parent::reset();

        $this->state = self::STATE_NEW;
        $this->synchronized = false;
    }
}
