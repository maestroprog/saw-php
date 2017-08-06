<?php

namespace Maestroprog\Saw\Application;

use Maestroprog\Saw\Application\ApplicationInterface as App;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\Pool\PoolOfUniqueThreads;

/**
 * Контейнер приложений.
 * Нужен для управления приложениями контроллером и воркерами.
 */
final class ApplicationContainer implements \Countable
{
    private $threadPools;

    /**
     * @var App[]
     */
    private $apps;
    private $currentApp;

    public function __construct(ContainerOfThreadPools $threadPools)
    {
        $this->threadPools = $threadPools;
        $this->apps = new \ArrayObject();
    }

    public function add(App $application): App
    {
        if (isset($this->apps[$application->getId()])) {
            throw new \RuntimeException('The application has already added.');
        }
        $this->threadPools->add($application->getId(), new PoolOfUniqueThreads());
        return $this->apps[$application->getId()] = $this->switchTo($application);
    }

    public function get(string $id): App
    {
        if (!isset($this->apps[$id])) {
            throw new \UnexpectedValueException('Unexpected application id not found: ' . $id);
        }
        return $this->apps[$id];
    }

    public function getCurrentApp(): App
    {
        return $this->currentApp;
    }

    public function switchTo(App $application): App
    {
        $this->threadPools->switchTo($this->threadPools->get($application->getId()));
        return $this->currentApp = $application;
    }

    /**
     * todo use
     */
    public function switchReset()
    {
        $this->currentApp = null;
    }

    /**
     * Запускает все приложения в контейнере.
     */
    public function run()
    {
        foreach ($this->apps as $app) {
            $this->switchTo($app)->run();
        }
    }

    /**
     * @return ContainerOfThreadPools
     */
    public function getThreadPools(): ContainerOfThreadPools
    {
        return $this->threadPools;
    }

    public function count()
    {
        return $this->apps->count();
    }
}
