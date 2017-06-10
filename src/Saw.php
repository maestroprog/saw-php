<?php

namespace Saw;

use Esockets\base\Configurator;
use Saw\Application\ApplicationInterface;
use Saw\Config\ApplicationConfig;
use Saw\Config\DaemonConfig;
use Saw\Service\ApplicationLoader;

/**
 * Класс-синглтон, реализующий загрузку Saw приложения Saw.
 */
final class Saw
{
    const ERROR_APPLICATION_CLASS_NOT_EXISTS = 1;
    const ERROR_WRONG_APPLICATION_CLASS = 2;

    private static $instance;

    private $factory;
    /**
     * @var ApplicationLoader
     */
    private $applicationLoader;

    public static function factory(): SawFactory
    {
        return self::instance()->factory;
    }

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

    /**
     * Инициализация фреймворка с заданным конфигом.
     *
     * @param array $config
     * @return Saw
     */
    public function init(array $config): self
    {
        foreach (['factory', 'daemon', 'sockets', 'application',] as $check) {
            if (!isset($config[$check]) || !is_array($config[$check])) {
                $config[$check] = [];
            }
        }
        $this->factory = new SawFactory(
            $config['factory'],
            new DaemonConfig($config['daemon']),
            new Configurator($config['sockets'])
        );

        $this->applicationLoader = new ApplicationLoader(
            new ApplicationConfig($config['application']),
            $this->factory
        );

        return $this;
    }

    /**
     * Инстанцирование приложения.
     *
     * @param string $appClass
     * @return ApplicationInterface
     */
    public function instanceApp(string $appClass): ApplicationInterface
    {
        return $this->applicationLoader->instanceApp($appClass);
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
