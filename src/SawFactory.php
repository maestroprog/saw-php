<?php

namespace Saw;

use Esockets\base\Configurator;
use Esockets\Client;
use Esockets\Server;
use Saw\Application\ApplicationContainer;
use Saw\Application\Context\ContextPool;
use Saw\Config\ControllerConfig;
use Saw\Config\DaemonConfig;
use Saw\Connector\ControllerConnector;
use Saw\Memory\SharedMemoryBySocket;
use Saw\Service\CommandDispatcher;
use Saw\Service\ControllerStarter;
use Saw\Service\Executor;
use Saw\Service\WorkerStarter;
use Saw\Standalone\ControllerCore;
use Saw\Standalone\WorkerCore;
use Saw\Thread\Runner\WebThreadRunner;

/**
 * Фабрика всех сервисов и обхектов для пилы.
 */
final class SawFactory
{
    const CALL_POINTER = '!';

    private $config;
    private $daemonConfig;
    private $socketConfigurator;

    private $controllerClient;
    private $commandDispatcher;
    private $executor;
    private $controllerStarter;
    private $sharedMemory;
    private $controllerConnector;
    private $webThreadRunner;
    private $workerStarter;
    private $controllerServer;
    private $controllerCore;

    private $controllerConfig;

    public function __construct(
        array $config,
        DaemonConfig $daemonConfig,
        Configurator $socketConfigurator,
        ControllerConfig $controllerConfig
    )
    {
        if (!isset($config['starter'])) {
            $config['starter'] = '-r "require \'bootstrap.php\'"';
        }
        $this->config = $config;
        $this->daemonConfig = $daemonConfig;
        $this->socketConfigurator = $socketConfigurator;
        $this->controllerConfig = $controllerConfig;
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
                    ? $this->daemonConfig->getControllerPath()
                    : $this->config['starter'],
                $this->daemonConfig->getControllerPid()
            );
    }

    public function getWorkerStarter(): WorkerStarter
    {
        return $this->workerStarter
            ?? $this->workerStarter = new WorkerStarter(
                $this->getExecutor(),
                $this->config['starter']
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
            ?? $this->sharedMemory = new SharedMemoryBySocket($this->getControllerConnector());
    }

    public function getControllerConnector(): ControllerConnector
    {
        return $this->controllerConnector
            ?? $this->controllerConnector
                = new ControllerConnector(
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

    public function getWebThreadRunner(): WebThreadRunner
    {
        return $this->webThreadRunner
            ?? $this->webThreadRunner
                = new WebThreadRunner($this->getControllerClient(), $this->getCommandDispatcher());
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
            ?? $this->applicationContainer = new ApplicationContainer();
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
}
