<?php

namespace Maestroprog\Saw\Di;

use Esockets\Client;
use Esockets\Server;
use Maestroprog\Container\HasContainerLinkInterface;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\ContainerOfCommands;
use Maestroprog\Saw\Config\ApplicationConfig;
use Maestroprog\Saw\Config\SawConfig;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Connector\WebControllerConnector;
use Maestroprog\Saw\Connector\WorkerControllerConnector;
use Maestroprog\Saw\Service\ApplicationLoader;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Service\ControllerRunner;
use Maestroprog\Saw\Service\ControllerStarter;
use Maestroprog\Saw\Service\Executor;
use Maestroprog\Saw\Service\WorkerStarter;
use Maestroprog\Saw\Standalone\Controller;
use Maestroprog\Saw\Standalone\Controller\ControllerWorkCycle;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;
use Maestroprog\Saw\Standalone\ControllerCore;
use Maestroprog\Saw\Standalone\Worker\WorkerThreadCreator;
use Maestroprog\Saw\Standalone\WorkerCore;
use Maestroprog\Saw\Thread\Creator\ThreadCreator;
use Maestroprog\Saw\Thread\Creator\ThreadCreatorInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\Runner\AsyncRemoteThreadRunner;
use Maestroprog\Saw\Thread\Runner\AsyncThreadRunner;
use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;
use Maestroprog\Saw\Thread\Synchronizer\AsyncSynchronizer;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizerInterface;
use Maestroprog\Saw\ValueObject\SawEnv;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qwerty\Application\ApplicationFactory;

class SawContainer implements HasContainerLinkInterface
{
    /** @var ContainerInterface */
    protected $container;
    private $config;
    private $daemonConfig;
    private $socketConfigurator;
    private $controllerConfig;
    private $environment;

    public function __construct(
        SawConfig $sawConfig,
        SawEnv $env
    )
    {
        $this->config = $sawConfig->getConfig();
        $this->daemonConfig = $sawConfig->getDaemonConfig();
        $this->socketConfigurator = $sawConfig->getSocketConfigurator();
        $this->controllerConfig = $sawConfig->getControllerConfig();
        $this->environment = $env;
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function get(string $id)
    {
        try {
            return $this->container->get($id);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return null;
        }
    }

    public function getEnvironment(): SawEnv
    {
        return $this->environment ?? SawEnv::web();
    }

    public function getControllerRunner(): ControllerRunner
    {
        return new ControllerRunner(
            $this->get(Executor::class),
            $this->daemonConfig
        );
    }

    public function getControllerStarter(): ControllerStarter
    {
        return new ControllerStarter(
            $this->get(ControllerRunner::class),
            $this->get(Client::class),
            $this->daemonConfig
        );
    }

    public function getWorkerStarter(): WorkerStarter
    {
        return new WorkerStarter(
            $this->get(Executor::class),
            $this->daemonConfig
        );
    }

    public function getExecutor(): Executor
    {
        return new Executor();
    }

    public function getControllerConnector(): ControllerConnectorInterface
    {
        if ($this->environment->isWeb()) {
            return $this->getWebControllerConnector(); // use internal
        } elseif ($this->environment->isWorker()) {
            return $this->getWorkerControllerConnector(); // use internal
        }
        throw new \LogicException('Unknown using ControllerConnector');
    }

    /**
     *
     * @return WebControllerConnector
     */
    public function getWebControllerConnector(): WebControllerConnector
    {
        return new WebControllerConnector(
            $this->get('ControllerClient'),
            $this->daemonConfig->getControllerAddress(),
            $this->get(CommandDispatcher::class),
            $this->get(ControllerStarter::class)
        );
    }

    /**
     *
     * @return WorkerControllerConnector
     */
    public function getWorkerControllerConnector(): WorkerControllerConnector
    {
        return new WorkerControllerConnector(
            $this->get('ControllerClient'),
            $this->daemonConfig->getControllerAddress(),
            $this->get(CommandDispatcher::class),
            $this->get(ControllerStarter::class)
        );
    }

    public function getControllerClient(): Client
    {
        return $this->socketConfigurator->makeClient();
    }

    public function getControllerServer(): Server
    {
        $controllerServer = $this->socketConfigurator->makeServer();
        if (!$controllerServer->isConnected()) {
            $controllerServer->connect($this->daemonConfig->getListenAddress());
        }
        return $controllerServer;
    }

    public function getCommandDispatcher(): CommandDispatcher
    {
        return new CommandDispatcher($this->get(ContainerOfCommands::class));
    }

    public function getThreadRunner(): ThreadRunnerInterface
    {
        return $this->environment->isWorker()
            ? $this->getRemoteThreadRunner() // use internal
            : (
                $this->config['multiThreading']['disabled'] ?? false
                    ? $this->getAsyncThreadRunner()
                    : $this->getRemoteThreadRunner() // use internal
            );
    }

    /**
     *
     * @return AsyncThreadRunner
     */
    public function getAsyncThreadRunner(): AsyncThreadRunner
    {
        return new AsyncThreadRunner();
    }

    /**
     *
     * @return AsyncRemoteThreadRunner
     */
    public function getRemoteThreadRunner(): AsyncRemoteThreadRunner
    {
        return new AsyncRemoteThreadRunner(
            $this->get(ControllerConnectorInterface::class),
            $this->get(CommandDispatcher::class),
            $this->get(Commander::class),
            $this->get(ApplicationContainer::class),
            $this->environment
        );
    }

    public function getControllerCore(): ControllerCore
    {
        return new ControllerCore(
            $this->get(Server::class),
            $this->get(CommandDispatcher::class),
            $this->get(Commander::class),
            $this->get(WorkerStarter::class),
            $this->controllerConfig,
            $this->get(ControllerRunner::class)
        );
    }

    public function getApplicationContainer(): ApplicationContainer
    {
        return new ApplicationContainer($this->get(ContainerOfThreadPools::class));
    }

    public function getWorkerCore(): WorkerCore
    {
        return new WorkerCore(
            $this->get('ControllerClient'),
            $this->get(CommandDispatcher::class),
            $this->get(Commander::class),
            $this->get(ApplicationContainer::class),
            $this->get(ApplicationLoader::class)
        );
    }

    public function getApplicationLoader(): ApplicationLoader
    {
        return new ApplicationLoader(
            new ApplicationConfig($this->config['application']),
            new ApplicationFactory($this->container)
        );
    }

    public function getContainerOfUniqueThreadPools(): ContainerOfThreadPools
    {
        return new ContainerOfThreadPools();
    }

    public function getThreadCreator(): ThreadCreatorInterface
    {
        return $this->environment->isWorker()
            ? $this->getWorkerThreadCreator() // use internal
            : $this->getWebThreadCreator(); // use internal
    }

    /**
     *
     * @return WorkerThreadCreator
     */
    public function getWorkerThreadCreator(): WorkerThreadCreator
    {
        return new WorkerThreadCreator(
            $this->get(ContainerOfThreadPools::class),
            $this->get(ApplicationContainer::class),
            $this->get(Commander::class),
            $this->get('ControllerClient')
        );
    }

    /**
     *
     * @return ThreadCreator
     */
    public function getWebThreadCreator(): ThreadCreator
    {
        return new ThreadCreator(
            $this->get(ContainerOfThreadPools::class),
            $this->get(ApplicationContainer::class)
        );
    }

    public function getThreadSynchronizer(): SynchronizerInterface
    {
        return new AsyncSynchronizer(
            $this->get(ThreadRunnerInterface::class),
            $this->get(ThreadRunnerInterface::class)->work(),
            $this->environment
        );
    }

    public function getMultiThreadingProvider(): MultiThreadingProvider
    {
        return new MultiThreadingProvider(
            $this->get(ContainerOfThreadPools::class),
            $this->get(ThreadCreatorInterface::class),
            $this->get(ThreadRunnerInterface::class),
            $this->get(SynchronizerInterface::class)
        );
    }

    public function getContainerOfCommands(): ContainerOfCommands
    {
        return new ContainerOfCommands();
    }

    public function getCommander(): Commander
    {
        return new Commander(
            $this->get('WorkCycle'),
            $this->get('ContainerOfCommands')
        );
    }

    public function getWorkCycle(): CycleInterface
    {
        if ($this->environment->isWeb()) {
            return $this->getWebWorkCycle();
        } elseif ($this->environment->isWorker()) {
            return $this->getWorkerWorkCycle();
        }
        return $this->getControllerWorkCycle();
    }

    /**
     * @return CycleInterface
     *
     */
    public function getWebWorkCycle(): CycleInterface
    {
        return $this->get(ControllerConnectorInterface::class);
    }

    /**
     * @return CycleInterface
     *
     */
    public function getWorkerWorkCycle(): CycleInterface
    {
        return $this->get(ControllerConnectorInterface::class);
    }

    /**
     * @return CycleInterface
     *
     */
    public function getControllerWorkCycle(): CycleInterface
    {
        return new ControllerWorkCycle($this->get('ControllerServer'));
    }

    public function getController(): Controller
    {
        return new Controller(
            $this->container->get('WorkCycle'),
            $this->container->get(ControllerCore::class),
            $this->container->get('ControllerServer'),
            $this->container->get(CommandDispatcher::class),
            $this->daemonConfig->getControllerPid()
        );
    }
}
