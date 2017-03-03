<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 25.02.2017
 * Time: 15:27
 */

namespace maestroprog\saw\Application;


use maestroprog\saw\Dto\Result;
use maestroprog\saw\Thread\Thread;

class Basic implements ApplicationInterface
{
    protected $id;

    public function __construct(string $id, array $config)
    {

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

    abstract public function runThreads();

    public function run()
    {
        $this->init();

        $this->main();

        return $this->end();
    }

    public function end(): Result
    {
        return ;
    }

    public function thread(string $uniqueId, \Closure $code): Thread
    {
        // TODO: Implement thread() method.
    }


}