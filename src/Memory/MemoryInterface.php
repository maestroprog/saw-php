<?php

namespace Saw\Memory;

/**
 * Описывает базовые возможности работы с памятью.
 */
interface MemoryInterface
{
    /**
     * Проверяет существование переменной в памяти.
     *
     * @param $varName
     * @return bool
     */
    public function has($varName): bool;

    /**
     * Читает переменную из памяти.
     *
     * @param $varName
     * @return mixed
     */
    public function read($varName);

    /**
     * Записывает переменную в память.
     *
     * @param $varName
     * @param $variable
     * @return bool
     */
    public function write($varName, $variable): bool;

    /**
     * Удаляет переменную из памяти.
     *
     * @param $varName
     * @return mixed
     */
    public function remove($varName);
}
