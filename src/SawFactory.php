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

    public function __construct(
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
}
