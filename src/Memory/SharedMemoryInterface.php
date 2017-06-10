<?php

namespace Saw\Memory;

/**
 * Интерфейс разделяемой памяти.
 * Разделяемая память является общей для всех воркеров.
 *
 * Описывает возможности разделяемой памяти.
 */
interface SharedMemoryInterface extends MemoryInterface
{
    const LOCK_TIMEOUT = 1000; // 1 second

    /**
     * @param $varName
     * @param bool $withLocking Позволяет залочить переменную при её чтении
     * @return mixed
     */
    public function read($varName, bool $withLocking = true);

    /**
     * @param $varName
     * @param $variable
     * @param bool $unlock Снимает блокировку с переменной после её перезаписи
     * @return bool
     */
    public function write($varName, $variable, bool $unlock = true): bool;

    public function lock($varName);

    public function unlock($varName);
}
