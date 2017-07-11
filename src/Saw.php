<?php

namespace Saw;

use Esockets\base\Configurator;
use Saw\Application\ApplicationInterface;
use Saw\Config\ApplicationConfig;
use Saw\Config\ControllerConfig;
use Saw\Config\DaemonConfig;
use Saw\Service\ApplicationLoader;
use Saw\Standalone\Controller;
use Saw\Standalone\Debugger;
use Saw\Standalone\Worker;
use Saw\ValueObject\SawEnv;

/**
 * Класс-синглтон, реализующий загрузку Saw приложения Saw.
 */
final class Saw
{
    const ERROR_APPLICATION_CLASS_NOT_EXISTS = 1;
    const ERROR_WRONG_APPLICATION_CLASS = 2;
    const ERROR_WRONG_CONFIG = 3;

    private static $instance;
    private static $debug;

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
     * @param array $config
     * @return Saw
     */
    public function init(array $config): self
    {
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
        return self::factory()->getApplicationContainer()->add($this->applicationLoader->instanceApp($appClass));
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
            $this->factory->getControllerCore(),
            $this->factory->getControllerServer(),
            $this->factory->getCommandDispatcher(),
            $this->factory->getDaemonConfig()->getControllerPid()
        );
    }

    public function instanceWorker(): Worker
    {
        $this->factory->setEnvironment(SawEnv::worker());
        return new Worker(
            $this->factory->getWorkerCore(),
            $this->factory->getWorkerControllerConnector()
        );
    }

    public function instanceDebugger(): Debugger
    {
        return new Debugger($this->factory->getWorkerControllerConnector());
    }

    /**
     * for singleton pattern
     */
    private function __clone()
    {
        ;
    }

    private function __sleep()
    {
        ;
    }

    private function __wakeup()
    {
        ;
    }
}
