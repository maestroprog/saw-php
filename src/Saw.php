<?php

namespace Maestroprog\Saw;

use Maestroprog\Container\Container;
use Maestroprog\Saw\Config\SawConfig;
use Maestroprog\Saw\Di\LazyContainer;
use Maestroprog\Saw\Di\MemoryContainer;
use Maestroprog\Saw\Di\SawContainer;
use Maestroprog\Saw\ValueObject\SawEnv;
use Psr\Container\ContainerInterface;

class Saw
{
    protected const CONTAINER_AUTOLOAD = false;

    private $config;
    private $env;
    /** @var ContainerInterface */
    private $container;

    public function __construct(string $configPath, SawEnv $env)
    {
        $this->config = new SawConfig($configPath);
        $this->env = $env;
        $containerInstantiation = function () use ($env) {
            $this->container = new Container();
            $this->container->register(new SawContainer(
                $this->config,
                $env
            ));
            $this->container->register(new MemoryContainer());

            foreach ($this->config->getConfig()['di'] as $customContainer) {
                if (is_object($customContainer)) {
                    $this->container->register($customContainer);
                } elseif (is_string($customContainer) && class_exists($customContainer)) {
                    $this->container->register(new $customContainer());
                }
            }

            return $this->container;
        };
        if (static::CONTAINER_AUTOLOAD) {
            $containerInstantiation();
        } else {
            $this->container = new LazyContainer($containerInstantiation);
        }
    }

    public function getConfig(): SawConfig
    {
        return $this->config;
    }

    public function getEnv(): SawEnv
    {
        return $this->env;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
