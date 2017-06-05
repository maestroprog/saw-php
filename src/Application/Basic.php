<?php

namespace Saw\Application;

use Saw\Dto\Result;
use Saw\Thread\Thread;

class Basic implements ApplicationInterface
{
    protected $id;

    public function __construct(string $id, array $config)
    {
        $this->id = $id;
    }

    final public function getId(): string
    {
        return $this->id;
    }

    public function init()
    {
        // TODO: Implement init() method.
    }

    public function main()
    {
        $this->runThreads();
    }

    abstract public function runThreads(): bool;

    public function run()
    {
        $this->init();

        $this->main();

        return $this->end();
    }

    public function end(): Result
    {
        return;
    }

    public function thread(string $uniqueId, callable $code): Thread
    {
        // TODO: Implement thread() method.
    }
}
