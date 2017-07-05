<?php

namespace Saw;

use Esockets\base\Configurator;
use Esockets\Client;
use Esockets\Server;
use Saw\Application\ApplicationContainer;
use Saw\Application\Context\ContextPool;
use Saw\Config\ControllerConfig;
use Saw\Config\DaemonConfig;
use Saw\Connector\ControllerConnectorInterface;
use Saw\Connector\WebControllerConnector;
use Saw\Connector\WorkerControllerConnector;
use Saw\Memory\SharedMemoryBySocket;
use Saw\Service\CommandDispatcher;
use Saw\Service\ControllerStarter;
use Saw\Service\Executor;
use Saw\Service\WorkerStarter;
use Saw\Standalone\ControllerCore;
use Saw\Standalone\Worker\WorkerThreadCreator;
use Saw\Standalone\WorkerCore;
use Saw\Thread\Creator\DummyThreadCreator;
use Saw\Thread\Creator\ThreadCreator;
use Saw\Thread\Creator\ThreadCreatorInterface;
use Saw\Thread\MultiThreadingProvider;
use Saw\Thread\Pool\ContainerOfThreadPools;
use Saw\Thread\Runner\DummyThreadRunner;
use Saw\Thread\Runner\ThreadRunnerInterface;
use Saw\Thread\Runner\WebThreadRunner;
use Saw\Thread\Runner\WorkerThreadRunner;
use Saw\Thread\Synchronizer\DummySynchronizer;
use Saw\Thread\Synchronizer\SynchronizerInterface;
use Saw\Thread\Synchronizer\WebThreadSynchronizer;
use Saw\ValueObject\SawEnv;

/**
 * Фабрика всех сервисов и обхектов для пилы.
 */
final class SawFactory
{
    const CALL_POINTER = '!';

    private $config;
    private $daemonConfig;
    private $socketConfigurator;
    private $controllerConfig;
    private $environment;

    private $controllerClient;
    private $commandDispatcher;
    private $executor;
    private $controllerStarter;
    private $sharedMemory;
    private $webControllerConnector;
    private $threadRunner;
    private $workerStarter;
    private $controllerServer;
    private $controllerCore;

    public function __construct(
        array $config,
        DaemonConfig $daemonConfig,
        Configurator $socketConfigurator,
        ControllerConfig $controllerConfig,
        SawEnv $env
    )
    {
        $workDir = __DIR__;
        if (!isset($config['controller_starter'])) {
            // todo config path
            $config['controller_starter'] = <<<CMD
-r "require_once '{$workDir}/src/bootstrap.php';
\Saw\Saw::instance()->init(require __DIR__ . '/../sample/config/saw.php')->instanceController()->start();"
CMD;
        }
        if (!isset($config['worker_starter'])) {
            $config['worker_starter'] = <<<CMD
-r "require_once __DIR__ . '/../src/bootstrap.php';
\Saw\Saw::instance()
    ->init(require __DIR__ . '/../sample/config/saw.php')
    ->instanceWorker()
    ->start();"
CMD;
        }
        $this->config = $config;
        $this->daemonConfig = $daemonConfig;
        $this->socketConfigurator = $socketConfigurator;
        $this->controllerConfig = $controllerConfig;
        $this->environment = $env;
    }

    /**
     * Устанавливает
     *
     * @param SawEnv $environment
     */
    public function setEnvironment(SawEnv $environment)
    {
        $this->environment = $environment;
    }

    public function getDaemonConfig(): DaemonConfig
    {
        return $this->daemonConfig;
    }

    public function getSocketConfigurator(): Configurator
    {
        return $this->socketConfigurator;
    }

    public function instanceArguments(array $arguments): array
    {
        $arguments = array_map(function ($argument) {
            if (is_array($argument)) {
                $arguments = [];
                if (isset($argument['arguments'])) {
                    $arguments = $this->instanceArguments($argument['arguments']);
                }
                if (isset($argument['method'])) {
                    $argument = call_user_func([$this, $argument['method']]);
                } else {
                    $argument = $arguments;
                }
            } elseif (self::CALL_POINTER === substr($argument, 0, 1)) {
                $argument = call_user_func([$this, substr($argument, 1)]);
            }
            return $argument;
        }, $arguments);
        return $arguments;
    }

    public function getControllerStarter(): ControllerStarter
    {
        return $this->controllerStarter
            ?? $this->controllerStarter = new ControllerStarter(
                $this->getExecutor(),
                $this->controllerClient,
                $this->daemonConfig->getControllerAddress(),
                $this->daemonConfig->hasControllerPath()
                    ? '-f ' . $this->daemonConfig->getControllerPath()
                    : $this->config['controller_starter'],
                $this->daemonConfig->getControllerPid()
            );
    }

    public function getWorkerStarter(): WorkerStarter
    {
        return $this->workerStarter
            ?? $this->workerStarter = new WorkerStarter(
                $this->getExecutor(),
                $this->daemonConfig->hasWorkerPath()
                    ? '-f ' . $this->daemonConfig->getWorkerPath()
                    : $this->config['worker_starter']
            );
    }

    public function getExecutor(): Executor
    {
        if (is_null($this->executor)) {
            $phpPath = null;
            if (isset($this->config['executor'])) {
                $phpPath = $this->config['executor'];
            }
            $this->executor = new Executor($phpPath);
        }
        return $this->executor;
    }

    public function getSharedMemory(): SharedMemoryBySocket
    {
        return $this->sharedMemory
            ?? $this->sharedMemory = new SharedMemoryBySocket($this->getWebControllerConnector());
    }

    public function getWebControllerConnector(): ControllerConnectorInterface
    {
        return $this->webControllerConnector
            ?? $this->webControllerConnector
                = new WebControllerConnector(
                $this->getControllerClient(),
                $this->daemonConfig->getControllerAddress(),
                $this->getCommandDispatcher(),
                $this->getControllerStarter()
            );
    }

    private $workerControllerConnector;

    public function getWorkerControllerConnector(): ControllerConnectorInterface
    {
        return $this->workerControllerConnector
            ??  $this->workerControllerConnector
                = new WorkerControllerConnector(
                $this->getControllerClient(),
                $this->daemonConfig->getControllerAddress(),
                $this->getCommandDispatcher(),
                $this->getControllerStarter()
            );
    }

    public function getControllerClient(): Client
    {
        return $this->controllerClient ?? $this->controllerClient = $this->socketConfigurator->makeClient();
    }

    public function getControllerServer(): Server
    {
        $this->controllerServer or $this->controllerServer = $this->socketConfigurator->makeServer();
        if (!$this->controllerServer->isConnected()) {
            $this->controllerServer->connect($this->getDaemonConfig()->getListenAddress());
        }
        return $this->controllerServer;
    }

    public function getCommandDispatcher(): CommandDispatcher
    {
        return $this->commandDispatcher
            ?? $this->commandDispatcher = new CommandDispatcher();
    }

    public function getThreadRunner(): ThreadRunnerInterface
    {
        return $this->threadRunner
            ?? $this->threadRunner
                = $this->environment->isWorker()
                ? new WorkerThreadRunner(
                    $this->getControllerClient(),
                    $this->getCommandDispatcher(),
                    $this->getApplicationContainer()
                )
                : (
                    $this->config['multiThreading']['disabled'] ?? false
                        ? new DummyThreadRunner()
                        : new WebThreadRunner($this->getWebControllerConnector())
                );
    }

    public function getControllerCore(): ControllerCore
    {
        return $this->controllerCore
            ?? $this->controllerCore = new ControllerCore(
                $this->getControllerServer(),
                $this->getCommandDispatcher(),
                $this->getWorkerStarter(),
                $this->controllerConfig
            );
    }

    private $workerCore;
    private $applicationContainer;

    public function getApplicationContainer(): ApplicationContainer
    {
        return $this->applicationContainer
            ?? $this->applicationContainer = new ApplicationContainer($this->getContainerOfUniqueThreadPools());
    }

    public function getWorkerCore(): WorkerCore
    {
        return $this->workerCore
            ?? $this->workerCore = new WorkerCore(
                $this->getControllerClient(),
                $this->getCommandDispatcher(),
                $this->getApplicationContainer(),
                Saw::instance()->getApplicationLoader()
            );
    }

    public function getContextPool(): ContextPool
    {
        return new ContextPool();
    }

    private $containerOfThreadPools;

    public function getContainerOfUniqueThreadPools(): ContainerOfThreadPools
    {
        return $this->containerOfThreadPools
            ?? $this->containerOfThreadPools = new ContainerOfThreadPools();
    }

    private $threadCreator;

    public function getThreadCreator(): ThreadCreatorInterface
    {
        return $this->threadCreator
            ?? $this->threadCreator
                = $this->environment->isWorker()
                ? new WorkerThreadCreator(
                    $this->getContainerOfUniqueThreadPools(),
                    $this->getCommandDispatcher(),
                    $this->getControllerClient()
                )
                : (
                    $this->config['multiThreading']['disabled'] ?? false
                        ? new DummyThreadCreator()
                        : new ThreadCreator($this->getContainerOfUniqueThreadPools())
                );
    }


    private $threadSynchronizer;

    public function getThreadSynchronizer(): SynchronizerInterface
    {
        return $this->threadSynchronizer
            ?? (
                $this->config['multiThreading']['disabled'] ?? false
                    ? new DummySynchronizer()
                    : new WebThreadSynchronizer(
                        $this->getThreadRunner(),
                        $this->getWebControllerConnector()
                    )
            );
    }

    private $multiThreadingProvider;

    public function getMultiThreadingProvider(): MultiThreadingProvider
    {
        return $this->multiThreadingProvider
            ?? $this->multiThreadingProvider = new MultiThreadingProvider(
                $this->getContainerOfUniqueThreadPools(),
                $this->getThreadCreator(),
                $this->getThreadRunner(),
                $this->getThreadSynchronizer()
            );
    }
}
