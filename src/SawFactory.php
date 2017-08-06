<?php

namespace Maestroprog\Saw;

use Esockets\base\Configurator;
use Esockets\Client;
use Esockets\Server;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Config\ControllerConfig;
use Maestroprog\Saw\Config\DaemonConfig;
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

/**
 * Фабрика всех сервисов и обхектов для пилы.
 */
final class SawFactory
{
    const CALL_POINTER = '!';
    const VAR_POINTER = '@';

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
\Maestroprog\Saw\Saw::instance()->init(require __DIR__ . '/../sample/config/saw.php')->instanceController()->start();"
CMD;
        }
        if (!isset($config['worker_starter'])) {
            $config['worker_starter'] = <<<CMD
-r "require_once __DIR__ . '/../src/bootstrap.php';
\Maestroprog\Saw\Saw::instance()
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

    public function instanceArguments(array $arguments, array $variables = []): array
    {
        $arguments = array_map(function ($argument) use ($variables) {
            if (is_array($argument)) {
                $arguments = [];
                if (isset($argument['arguments'])) {
                    $arguments = $this->instanceArguments($argument['arguments'], $variables);
                }
                if (isset($argument['method'])) {
                    $argument = call_user_func([$this, $argument['method']]);
                } else {
                    $argument = $arguments;
                }
            } else {
                $char = substr($argument, 0, 1);
                if (self::CALL_POINTER === $char) {
                    $argument = call_user_func([$this, substr($argument, 1)]);
                } elseif (self::VAR_POINTER === $char) {
                    $argument = $variables[substr($argument, 1)] ?? null;
                }
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

    public function getSharedMemory(): SharedMemoryInterface
    {
        return $this->sharedMemory
            ?? $this->sharedMemory = new SharedMemoryOnSocket($this->getControllerConnector());
    }

    public function getControllerConnector(): ControllerConnectorInterface
    {
        if ($this->environment->isWeb()) {
            return $this->getWebControllerConnector();
        }
        return $this->getWorkerControllerConnector();
    }

    private function getWebControllerConnector(): ControllerConnectorInterface
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

    private function getWorkerControllerConnector(): ControllerConnectorInterface
    {
        return $this->workerControllerConnector
            ?? $this->workerControllerConnector
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
                        : new WebThreadRunner($this->getControllerConnector())
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
                        $this->getControllerConnector()
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
