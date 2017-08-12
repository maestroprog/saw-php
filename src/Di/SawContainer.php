<?php

namespace Maestroprog\Saw\Di;

use Esockets\base\Configurator;
use Esockets\Client;
use Esockets\Server;
use Maestroprog\Saw\Config\ControllerConfig;
use Maestroprog\Saw\Config\DaemonConfig;
use Maestroprog\Saw\Saw;
use Maestroprog\Container\AbstractBasicContainer;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Connector\WebControllerConnector;
use Maestroprog\Saw\Connector\WorkerControllerConnector;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Memory\SharedMemoryOnSocket;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\ControllerStarter;
use Maestroprog\Saw\Service\Executor;
use Maestroprog\Saw\Service\WorkerStarter;
use Maestroprog\Saw\Standalone\ControllerCore;
use Maestroprog\Saw\Standalone\Worker\WorkerThreadCreator;
use Maestroprog\Saw\Standalone\Worker\WorkerThreadRunner;
use Maestroprog\Saw\Standalone\WorkerCore;
use Maestroprog\Saw\Thread\Creator\DummyThreadCreator;
use Maestroprog\Saw\Thread\Creator\ThreadCreator;
use Maestroprog\Saw\Thread\Creator\ThreadCreatorInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\Runner\DummyThreadRunner;
use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;
use Maestroprog\Saw\Thread\Runner\WebThreadRunner;
use Maestroprog\Saw\Thread\Synchronizer\DummySynchronizer;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizerInterface;
use Maestroprog\Saw\Thread\Synchronizer\WebThreadSynchronizer;
use Maestroprog\Saw\ValueObject\SawEnv;

class SawContainer extends AbstractBasicContainer
{
    private $config;
    private $daemonConfig;
    private $socketConfigurator;
    private $controllerConfig;
    private $environment;

    public function __construct(
        array $config,
        DaemonConfig $daemonConfig,
        Configurator $socketConfigurator,
        ControllerConfig $controllerConfig,
        SawEnv $env
    )
    {
        $this->config = $config;
        $this->daemonConfig = $daemonConfig;
        $this->socketConfigurator = $socketConfigurator;
        $this->controllerConfig = $controllerConfig;
        $this->environment = $env;
    }

    public function setEnvironment(SawEnv $environment)
    {
        $this->environment = $environment;
    }

    public function getControllerStarter(): ControllerStarter
    {
        return new ControllerStarter(
            $this->get(Executor::class),
            $this->get(Client::class),
            $this->daemonConfig->getControllerAddress(),
            $this->daemonConfig->hasControllerPath()
                ? '-f ' . $this->daemonConfig->getControllerPath()
                : $this->config['controller_starter'],
            $this->daemonConfig->getControllerPid()
        );
    }

    public function getWorkerStarter(): WorkerStarter
    {
        return new WorkerStarter(
            $this->get(Executor::class),
            $this->daemonConfig->hasWorkerPath()
                ? '-f ' . $this->daemonConfig->getWorkerPath()
                : $this->config['worker_starter']
        );
    }

    public function getExecutor(): Executor
    {
        $phpPath = null;
        if (isset($this->config['executor'])) {
            $phpPath = $this->config['executor'];
        }
        return new Executor($phpPath);
    }

    public function getSharedMemory(): SharedMemoryInterface
    {
        return new SharedMemoryOnSocket($this->get(ControllerConnectorInterface::class));
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
        return new CommandDispatcher();
    }

    public function getThreadRunner(): ThreadRunnerInterface
    {
        return $this->environment->isWorker()
            ? $this->getWorkerThreadRunner() // use internal
            : (
                $this->config['multiThreading']['disabled'] ?? false
                    ? new DummyThreadRunner()
                    : $this->getWebThreadRunner() // use internal
            );
    }

    public function getControllerCore(): ControllerCore
    {
        return new ControllerCore(
            $this->get(Server::class),
            $this->get(CommandDispatcher::class),
            $this->get(WorkerStarter::class),
            $this->controllerConfig
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
            $this->get(ApplicationContainer::class),
            Saw::instance()->getApplicationLoader()
        );
    }

    public function getContextPool(): ContextPool
    {
        return new ContextPool();
    }

    public function getContainerOfUniqueThreadPools(): ContainerOfThreadPools
    {
        return new ContainerOfThreadPools();
    }

    public function getThreadCreator(): ThreadCreatorInterface
    {
        return $this->environment->isWorker()
            ? $this->getWorkerThreadCreator() // use internal
            : (
                $this->config['multiThreading']['disabled'] ?? false
                    ? new DummyThreadCreator()
                    : $this->getWebThreadCreator() // use internal
            );
    }

    public function getThreadSynchronizer(): SynchronizerInterface
    {
        return $this->config['multiThreading']['disabled'] ?? false
                ? new DummySynchronizer()
                : new WebThreadSynchronizer(
                    $this->get(ThreadRunnerInterface::class),
                    $this->get(ControllerConnectorInterface::class)
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

    /**
     * @internal
     * @return WebControllerConnector
     */
    private function getWebControllerConnector(): WebControllerConnector
    {
        return new WebControllerConnector(
            $this->get('ControllerClient'),
            $this->daemonConfig->getControllerAddress(),
            $this->get(CommandDispatcher::class),
            $this->get(ControllerStarter::class)
        );
    }

    /**
     * @internal
     * @return WorkerControllerConnector
     */
    private function getWorkerControllerConnector(): WorkerControllerConnector
    {
        return new WorkerControllerConnector(
            $this->get('ControllerClient'),
            $this->daemonConfig->getControllerAddress(),
            $this->get(CommandDispatcher::class),
            $this->get(ControllerStarter::class)
        );
    }

    /**
     * @internal
     * @return WorkerThreadRunner
     */
    private function getWorkerThreadRunner(): WorkerThreadRunner
    {
        return new WorkerThreadRunner(
            $this->get('ControllerClient'),
            $this->get(CommandDispatcher::class),
            $this->get(ApplicationContainer::class)
        );
    }

    /**
     * @internal
     * @return WebThreadRunner
     */
    private function getWebThreadRunner(): WebThreadRunner
    {
        return new WebThreadRunner($this->get(ControllerConnectorInterface::class));
    }

    /**
     * @internal
     * @return WorkerThreadCreator
     */
    private function getWorkerThreadCreator(): WorkerThreadCreator
    {
        return new WorkerThreadCreator(
            $this->get(ContainerOfThreadPools::class),
            $this->get(CommandDispatcher::class),
            $this->get('ControllerClient')
        );
    }

    /**
     * @internal
     * @return ThreadCreator
     */
    private function getWebThreadCreator(): ThreadCreator
    {
        return new ThreadCreator($this->get(ContainerOfThreadPools::class));
    }
}