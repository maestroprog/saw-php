<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 29.10.2016
 * Time: 19:51
 */

namespace maestroprog\saw\library;

class Singleton
{
    protected static $instance;

    private function __construct()
    {
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        static::$instance or static::$instance = new static();
        return static::$instance;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    private function __sleep()
    {
        // TODO: Implement __sleep() method.
    }

    private function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }
}
