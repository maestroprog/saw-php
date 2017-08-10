<?php

namespace Maestroprog\Saw\Heading;

abstract class Singleton
{
    protected static $instance = [];

    /**
     * @return static|self
     */
    public static function instance()
    {
        if (!isset(static::$instance[static::class])) {
            static::$instance[static::class] = new static();
        }
        return static::$instance[static::class];
    }

    private function __construct()
    {
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
