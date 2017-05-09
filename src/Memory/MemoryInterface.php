<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 17.03.2017
 * Time: 22:41
 */

namespace maestroprog\saw\Memory;


interface MemoryInterface
{
    /**
     * Ищет в памяти переменную.
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