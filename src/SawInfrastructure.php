<?php

namespace Maestroprog\Saw;

use Maestroprog\Container\Container;
use Maestroprog\Saw\Di\MemoryContainer;
use Maestroprog\Saw\Di\SawContainer;
use Maestroprog\Saw\Standalone\Controller;
use Maestroprog\Saw\Standalone\Debugger;
use Maestroprog\Saw\Standalone\Worker;
use Maestroprog\Saw\ValueObject\SawEnv;

class SawInfrastructure extends Saw
{
    private $container;

    public function __construct(string $configPath, SawEnv $env)
    {
        parent::__construct($configPath, $env);

        $this->container = new Container();
        $this->container->register(new SawContainer(
            $this->getConfig(),
            $env
        ));
        $this->container->register(new MemoryContainer());
/*
        $this->applicationLoader = new ApplicationLoader(
            new ApplicationConfig($config['application']),
            new ApplicationFactory($this->container)
        );*/
    }

    public function controller(): Controller
    {
        return $this->container->get(Controller::class);
    }

    public function worker(): Worker
    {
        return $this->container->get(Worker::class);
    }

    public function debugger(): Debugger
    {
        return $this->container->get(Debugger::class);
    }
}
