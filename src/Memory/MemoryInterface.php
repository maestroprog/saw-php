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
     * @throws \OutOfBoundsException Если переменная не существует
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
     * @throws \OutOfBoundsException Если переменная не существует
     */
    public function remove(string $varName);

    /**
     * Достаёт из памяти список всех ключей и значений,
     * при этом для фильтрации ключей можно указать префикс.
     * Ресурсоёмкая операция.
     *
     * @param string $prefix
     * @return array
     */
    public function list(string $prefix = null): array;

    /**
     * Полная очистка памяти.
     * Освобождение занятого пространства.
     *
     * @return void
     */
    public function free();
}
