<?php

namespace Maestroprog\Saw;

use Esockets\base\Configurator;
use Maestroprog\Container\Container;
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

    private static $instance;
    private static $debug;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var SawFactory
     */
    private $factory;
    /**
     * @var ApplicationLoader
     */
    private $applicationLoader;

    /**
     * @return self
     */
    public static function instance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }

    private function __construct()
    {
        defined('INTERVAL') or define('INTERVAL', 10000);
        defined('SAW_DIR') or define('SAW_DIR', __DIR__);
    }

    public static function factory(): SawFactory
    {
        return self::instance()->factory;
    }

    /**
     * @return ApplicationInterface
     * @todo use
     */
    public static function getCurrentApp(): ApplicationInterface
    {
        return self::instance()->factory()->getApplicationContainer()->getCurrentApp();
    }

    /**
     * Инициализация фреймворка с заданным конфигом.
     *
     * @param string $configPath
     * @return Saw
     */
    public function init(string $configPath): self
    {
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

        $this->factory = new SawFactory(
            $config['factory'],
            new DaemonConfig($config['daemon']),
            new Configurator($config['sockets']),
            new ControllerConfig($config['controller']),
            SawEnv::web()
        );

        $this->applicationLoader = new ApplicationLoader(
            new ApplicationConfig($config['application']),
            $this->factory
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
        $this->container->register(new SawContainer(
            $config['factory'],
            new DaemonConfig($config['daemon']),
            new Configurator($config['sockets']),
            new ControllerConfig($config['controller']),
            SawEnv::web()
        ));

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
        $this->factory->setEnvironment(SawEnv::controller());
        return new Controller(
            $this->container->get(ControllerCore::class),
            $this->container->get('controllerServer'),
            $this->container->get(CommandDispatcher::class),
            $this->container->getDaemonConfig()->getControllerPid() // todo
        );
    }

    public function instanceWorker(): Worker
    {
        $this->factory->setEnvironment(SawEnv::worker());
        return new Worker(
            $this->container->get(WorkerCore::class),
            $this->container->get(ControllerConnectorInterface::class)
        );
    }

    public function instanceDebugger(): Debugger
    {
        $this->factory->setEnvironment(SawEnv::worker());
        return new Debugger($this->container->get(ControllerConnectorInterface::class));
    }
}
