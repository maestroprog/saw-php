<?php

namespace Maestroprog\Saw\Config;

use Esockets\Base\Configurator;

class SawConfig
{
    protected $config;
    protected $daemonConfig;
    protected $socketConfigurator;
    protected $controllerConfig;
    protected $appConfig;

    public function __construct(string $configPath)
    {
        $config = require __DIR__ . '/../../config/saw.php';
        array_walk($config, function (&$value, $key, $customConfig) {
            if (isset($customConfig[$key]) && is_array($value)) {
                $value = $customConfig[$key] + $value;
            }
        }, require $configPath);

        foreach (['saw', 'daemon', 'sockets', 'application', 'controller'] as $check) {
            if (!isset($config[$check]) || !is_array($config[$check])) {
                $config[$check] = [];
            }
        }

        $this->config = $config;
        $this->daemonConfig = new DaemonConfig($config['daemon'], $configPath);
        $this->socketConfigurator = new Configurator($config['sockets']);
        $this->controllerConfig = new ControllerConfig($config['controller']);
        $this->appConfig = new ApplicationConfig($config['application']);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getDaemonConfig(): DaemonConfig
    {
        return $this->daemonConfig;
    }

    public function getSocketConfigurator(): Configurator
    {
        return $this->socketConfigurator;
    }

    public function getControllerConfig(): ControllerConfig
    {
        return $this->controllerConfig;
    }

    public function isMultiThreadingDisabled(): bool
    {
        return (bool)$this->config['multiThreading']['disabled'] ?? false;
    }
}
