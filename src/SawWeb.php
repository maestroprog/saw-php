<?php

namespace Maestroprog\Saw;

use Maestroprog\Saw\Application\ApplicationConnector;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\ContainerOfCommands;
use Maestroprog\Saw\Config\ApplicationConfig;
use Maestroprog\Saw\Connector\WebControllerConnector;
use Maestroprog\Saw\Service\ApplicationLoader;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Service\ControllerRunner;
use Maestroprog\Saw\Service\ControllerStarter;
use Maestroprog\Saw\Service\Executor;
use Maestroprog\Saw\Thread\Creator\ThreadCreator;
use Maestroprog\Saw\Thread\MultiThreadingInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\Runner\AsyncRemoteThreadRunner;
use Maestroprog\Saw\Thread\Synchronizer\AsyncSynchronizer;
use Maestroprog\Saw\ValueObject\SawEnv;
use Qwerty\Application\ApplicationFactory;
use Qwerty\Application\ApplicationInterface;

class SawWeb extends Saw
{
    protected const CONTAINER_AUTOLOAD = false;

    protected $applicationLoader;
    protected $applicationConfig;
    protected $applicationContainer;

    public function __construct(string $configPath)
    {
        parent::__construct($configPath, SawEnv::web());
        $this->applicationConfig = new ApplicationConfig($this->getConfig()->getConfig()['application']);
        if ($this->getConfig()->isMultiThreadingDisabled()) {
            /** @var ApplicationContainer $container */
            $this->applicationContainer = $this->getContainer()->get(ApplicationContainer::class);
        } else {
            $threadPools = new ContainerOfThreadPools();
            $this->applicationContainer = new ApplicationContainer($threadPools);
        }
        if (!$this->applicationLoader) {
            $this->applicationLoader = new ApplicationLoader(
                $this->applicationConfig,
                new ApplicationFactory($this->getContainer())
            );
        }
    }

    public function app(string $applicationClass): ApplicationInterface
    {
        if ($this->getConfig()->isMultiThreadingDisabled()) {
            return $this->applicationContainer->add($this->applicationLoader->instanceApp($applicationClass));
        }

        return $this->applicationContainer->add(new ApplicationConnector(
            $this->applicationConfig->getApplicationIdByClass($applicationClass),
            $this->getMultiThreadingProvider()
        ));
    }

    public function thread(): MultiThreadingInterface
    {
    }

    public function getCurrentApp(): ApplicationInterface
    {
//        return self::instance()->container->get(ApplicationContainer::class)->getCurrentApp();
    }

    protected function getMultiThreadingProvider(): MultiThreadingProvider
    {
        $config = $this->getConfig();
        $commands = new ContainerOfCommands();
        $commandDispatcher = new CommandDispatcher($commands);
        $daemonConfig = $config->getDaemonConfig();
        $client = $config->getSocketConfigurator()->makeClient();
        $connectAddress = $daemonConfig->getControllerAddress();
        $webControllerConnector = new WebControllerConnector(
            $client,
            $connectAddress,
            $commandDispatcher,
            new ControllerStarter(
                new ControllerRunner(new Executor(), $daemonConfig),
                $client,
                $daemonConfig
            )
        );
        $threadRunner = new AsyncRemoteThreadRunner(
            $webControllerConnector,
            $commandDispatcher,
            new Commander($webControllerConnector, $commands),
            $this->applicationContainer,
            $this->getEnv()
        );

        return new MultiThreadingProvider(
            $this->applicationContainer->getThreadPools(),
            new ThreadCreator($this->applicationContainer->getThreadPools(), $this->applicationContainer),
            $threadRunner,
            new AsyncSynchronizer($threadRunner, $threadRunner->work(), $this->getEnv())
        );
    }
}
