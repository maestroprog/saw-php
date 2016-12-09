<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 09.12.16
 * Time: 20:39
 */

namespace maestroprog\saw\entity;


class Command
{
    public function __construct(string $name, string $class, callable $executor)
    {
    }
}