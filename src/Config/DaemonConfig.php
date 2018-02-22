<?php

namespace Maestroprog\Saw\Config;

use Esockets\base\AbstractAddress;
use Maestroprog\Saw\Saw;

class DaemonConfig
{
    const CONFIG_MAP = [
        'controller_path' => 'controllerPath',
        'worker_path' => 'workerPath',
        'controller_pid' => 'controllerPid',
        'listen_address' => 'listenAddress',
        'controller_address' => 'controllerAddress',
    ];

    /** @var string */
    private $controllerPath;
    /** @var string */
    private $workerPath;
    /** @var string */
    private $configPath;
    /** @var string */
    private $controllerPid = 'controller.pid';

    /**
     * @var AbstractAddress
     */
    private $listenAddress;
    /**
     * @var AbstractAddress
     */
    private $controllerAddress;

    public function __construct(array $config, string $configPath)
    {
        foreach ($config as $key => $value) {
            if (!isset(self::CONFIG_MAP[$key])) {
                continue;
            }
            $mappedKey = self::CONFIG_MAP[$key];
            if (property_exists($this, $mappedKey)) {
                $this->{$mappedKey} = $value;
            }
        }
        $this->configPath = $configPath;
    }

    public function hasControllerPath(): bool
    {
        return isset($this->controllerPath) && file_exists($this->controllerPath);
    }

    public function hasWorkerPath(): bool
    {
        return isset($this->workerPath) && file_exists($this->workerPath);
    }

    /**
     * Вернёт путь к исполнимому скрипту контроллера.
     *
     * @return string
     */
    public function getControllerPath(): string
    {
        return $this->controllerPath;
    }

    /**
     * Вернёт путь к исполнимому скрипту воркера.
     *
     * @return string
     */
    public function getWorkerPath(): string
    {
        return $this->workerPath;
    }

    /**
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Вернёт путь к pid файлу контроллера.
     *
     * @return string
     */
    public function getControllerPid(): string
    {
        return $this->controllerPid;
    }

    public function getListenAddress(): AbstractAddress
    {
        if (!$this->listenAddress instanceof AbstractAddress) {
            throw new \RuntimeException('Listen address is not configured.', Saw::ERROR_WRONG_CONFIG);
        }
        return $this->listenAddress;
    }

    public function getControllerAddress(): AbstractAddress
    {
        if (!$this->controllerAddress instanceof AbstractAddress) {
            throw new \RuntimeException('Controller address is not configured.', Saw::ERROR_WRONG_CONFIG);
        }
        return $this->controllerAddress;
    }
}
