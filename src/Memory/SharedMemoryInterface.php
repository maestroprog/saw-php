<?php

namespace Maestroprog\Saw\Memory;

/**
 * Интерфейс разделяемой памяти.
 * Разделяемая память является общей для всех воркеров.
 *
 * Описывает возможности разделяемой памяти.
 */
interface SharedMemoryInterface extends MemoryInterface
{
    const READ_TIMEOUT = 2000; // 2 second
    const WRITE_TIMEOUT = 5000; // 5 second
    const LOCK_TIMEOUT = 1000; // 1 second

    /**
     * @param string $varName
     * @param bool $withLocking Позволяет залочить переменную перед её созданием
     * @return bool
     */
    public function has(string $varName, bool $withLocking = false): bool;

    /**
     * @param string $varName
     * @param bool $withLocking Позволяет залочить переменную при её чтении
     * @return mixed
     * @throws \OutOfBoundsException Если переменная не существует
     * @throws MemoryLockException Если переменная кем-то залочена
     */
    public function read(string $varName, bool $withLocking = true);

    /**
     * @param string $varName
     * @param $variable
     * @param bool $unlock Снимает блокировку с переменной после её перезаписи
     * @return bool
     * @throws MemoryLockException Если переменная кем-то залочена
     */
    public function write(string $varName, $variable, bool $unlock = true): bool;

    /**
     * @param string $varName
     * @return void
     * @throws \OutOfBoundsException Если переменная не существует
     * @throws MemoryLockException Если переменная кем-то залочена
     */
    public function lock(string $varName);

    /**
     * @param string $varName
     * @return void
     * @throws \OutOfBoundsException Если переменная не существует
     */
    public function unlock(string $varName);
}
