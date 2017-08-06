<?php

namespace Maestroprog\Saw\Memory;

/**
 * Описывает базовые возможности работы с памятью.
 */
interface MemoryInterface
{
    /**
     * Проверяет существование переменной в памяти.
     *
     * @param string $varName
     * @return bool
     */
    public function has(string $varName): bool;

    /**
     * Читает переменную из памяти.
     *
     * @param string $varName
     * @return mixed
     */
    public function read(string $varName);

    /**
     * Записывает переменную в память.
     *
     * @param string $varName
     * @param $variable
     * @return bool
     */
    public function write(string $varName, $variable): bool;

    /**
     * Удаляет переменную из памяти.
     *
     * @param string void
     */
    public function remove(string $varName);
}
