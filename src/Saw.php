<?php

namespace Maestroprog\Saw;

use Esockets\base\Configurator;
use Maestroprog\Container\Container;
use Maestroprog\Container\ContainerCompiler;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Application\ApplicationInterface;
use Maestroprog\Saw\Config\ApplicationConfig;
use Maestroprog\Saw\Config\ControllerConfig;
use Maestroprog\Saw\Config\DaemonConfig;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Di\SawContainer;
use Maestroprog\Saw\Heading\Singleton;
use Maestroprog\Saw\Service\ApplicationLoader;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Standalone\Controller;
use Maestroprog\Saw\Standalone\ControllerCore;
use Maestroprog\Saw\Standalone\Debugger;
use Maestroprog\Saw\Standalone\Worker;
use Maestroprog\Saw\Standalone\WorkerCore;
use Maestroprog\Saw\ValueObject\SawEnv;

/**
 * Класс-синглтон, реализующий загрузку Saw приложения Saw.
 */
final class Saw extends Singleton
{
    const ERROR_APPLICATION_CLASS_NOT_EXISTS = 1;
    const ERROR_WRONG_APPLICATION_CLASS = 2;
    const ERROR_WRONG_CONFIG = 3;

    /**
     * @var array
     */
    private $config;

    /**
     * @var DaemonConfig
     */
    private $daemonConfig;

    /**
     * @var ControllerConfig
     */
    private $controllerConfig;

    /**
     * @var SawEnv
     */
    private $environment;

    private static $debug;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var SawContainer
     */
    private $sawContainer;

    /**
     * @var ApplicationLoader
     */
    private $applicationLoader;

    /**
     * @return ApplicationInterface
     * @todo use
     */
    public static function getCurrentApp(): ApplicationInterface
    {
        return self::instance()->container->get(ApplicationContainer::class)->getCurrentApp();
    }

    /**
     * Инициализация фреймворка с заданным конфигом.
     *
     * @param string $configPath
     * @return Saw
     */
    public function init(string $configPath): self
    {
        defined('INTERVAL') or define('INTERVAL', 10000);
        defined('SAW_DIR') or define('SAW_DIR', __DIR__);
        $config = require_once $configPath;
        // todo include config
        foreach (['saw', 'factory', 'daemon', 'sockets', 'application', 'controller'] as $check) {
            if (!isset($config[$check]) || !is_array($config[$check])) {
                $config[$check] = [];
            }
        }
        if (isset($config['saw']['debug'])) {
            self::$debug = (bool)$config['saw']['debug'];
        }

        $workDir = __DIR__;
        if (!isset($config['controller_starter'])) {
            // todo config path
            $config['controller_starter'] = <<<CMD
-r "require_once '{$workDir}/bootstrap.php';
\Maestroprog\Saw\Saw::instance()
    ->init('{$workDir}/../config/saw.php')
    ->instanceController()
    ->start();"
CMD;
        }
        if (!isset($config['worker_starter'])) {
            $config['worker_starter'] = <<<CMD
-r "require_once '{$workDir}/bootstrap.php';
\Maestroprog\Saw\Saw::instance()
    ->init('{$workDir}/../config/saw.php')
    ->instanceWorker()
    ->start();"
CMD;
        }
        /*
        $this->factory = new SawFactory(
            $config['factory'],
            new DaemonConfig($config['daemon']),
            new Configurator($config['sockets']),
            new ControllerConfig($config['controller']),
            SawEnv::web()
        );*/
//        $this->environment = SawEnv::web();

        $this->applicationLoader = new ApplicationLoader(
            new ApplicationConfig($config['application']),
            $this
        );

        if (!self::$debug) {
            set_exception_handler(function (\Throwable $exception) {
                if ($exception instanceof \Exception) {
                    switch ($exception->getCode()) {
                        case self::ERROR_WRONG_CONFIG:
                            echo $exception->getMessage();
                            exit($exception->getCode());
                    }
                }
            });
        }

        $this->container = Container::instance();
        $this->container->register($this->sawContainer = new SawContainer(
            $this->config = $config,
            $this->daemonConfig = new DaemonConfig($config['daemon']),
            $this->config = new Configurator($config['sockets']),
            $this->controllerConfig = new ControllerConfig($config['controller']),
            SawEnv::web()
        ));
//        $compiler = new ContainerCompiler($this->container);
//        $compiler->compile('/var/tmp/');

        return $this;
    }

    public function getApplicationLoader(): ApplicationLoader
    {
        return $this->applicationLoader;
    }

    /**
     * Инстанцирование приложения.
     *
     * @param string $appClass
     * @return ApplicationInterface
     */
    public function instanceApp(string $appClass): ApplicationInterface
    {
        return $this
            ->container
            ->get(ApplicationContainer::class)
            ->add($this->applicationLoader->instanceApp($appClass));
    }

    /**
     * Создаёт новый инстанс объекта контроллера.
     *
     * @return Controller
     */
    public function instanceController(): Controller
    {
        $this->sawContainer->setEnvironment(SawEnv::controller());
        return new Controller(
            $this->container->get(ControllerCore::class),
            $this->container->get('ControllerServer'),
            $this->container->get(CommandDispatcher::class),
            $this->daemonConfig->getControllerPid() // todo
        );
    }

    public function instanceWorker(): Worker
    {
        $this->sawContainer->setEnvironment(SawEnv::worker());
        return new Worker(
            $this->container->get(WorkerCore::class),
            $this->container->get(ControllerConnectorInterface::class)
        );
    }

    public function instanceDebugger(): Debugger
    {
        $this->sawContainer->setEnvironment(SawEnv::worker());
        return new Debugger($this->container->get(ControllerConnectorInterface::class));
    }

    const VAR_POINTER = '@';

    public function instanceArguments(array $arguments, array $variables = []): array
    {
        $arguments = array_map(function ($argument) use ($variables) {
            if (is_array($argument)) {
                $arguments = [];
                if (isset($argument['arguments'])) {
                    $arguments = $this->instanceArguments($argument['arguments'], $variables);
                }
                if (isset($argument['service'])) {
                    $argument = $this->container->get($argument['service']);
                } else {
                    $argument = $arguments;
                }
            } else {
                $char = substr($argument, 0, 1);
                if (self::VAR_POINTER === $char) {
                    $argument = $variables[substr($argument, 1)] ?? null;
                } else {
                    $argument = $this->container->get($argument);
                }
            }
            return $argument;
        }, $arguments);
        return $arguments;
    }
}
