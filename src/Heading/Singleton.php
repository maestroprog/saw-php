<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 29.10.2016
 * Time: 19:51
 */

namespace maestroprog\saw\Heading;

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
        if (!isset(static::$instance[static::class])) {
            static::$instance[static::class] = new static();
        }
        return static::$instance[static::class];
    }

    private function __clone()
    {
    }

    private function __sleep()
    {
    }

    private function __wakeup()
    {
    }
}
