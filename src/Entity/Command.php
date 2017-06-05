<?php

namespace Saw\Entity;

final class Command
{
    private $name;
    private $class;
    private $executor;

    /**
     * Если executor не задан, то это означает,
     * что данная команда не будет выполняться,
     * но будет отправляться другим,
     * и принимать какие-то результаты выполнения.
     *
     * @param string $name
     * @param string $class
     * @param callable|null $executor
     */
    public function __construct(string $name, string $class, callable $executor = null)
    {
        $this->name = $name;
        $this->class = $class;
        $this->executor = $executor;
    }

    /**
     * @param $context \Saw\Heading\dispatcher\Command
     * @return mixed
     */
    public function exec(\Saw\Heading\dispatcher\Command $context)
    {
        return $this->isExecutable() ? call_user_func($this->executor, $context) : (var_dump($this->getName()));
    }

    public function getName()
    {
        return $this->name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function isExecutable()
    {
        return $this->executor !== null;
    }
}