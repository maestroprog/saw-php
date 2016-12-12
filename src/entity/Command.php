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
    private $name;
    private $class;
    private $executor;

    public function __construct(string $name, string $class, callable $executor)
    {
        $this->name = $name;
        $this->class = $class;
        $this->executor = $executor;
    }

    /**
     * @param $context \maestroprog\saw\library\Command
     * @return bool
     */
    public function exec($context): bool
    {
        return call_user_func($this->executor, $context);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getClass()
    {
        return $this->class;
    }
}
