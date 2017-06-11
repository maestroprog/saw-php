<?php

namespace Saw\Config;

use Esockets\base\AbstractAddress;
use Saw\Saw;

class DaemonConfig
{
    const CONFIG_MAP = [
        'controller_path' => 'controllerPath',
        'worker_path' => 'workerPath',
        'controller_pid' => 'controllerPid',
        'worker_pid' => 'workerPid',
        'listen_address' => 'listenAddress',
        'controller_address' => 'controllerAddress',
    ];

    private $controllerPath;
    private $workerPath;
    private $controllerPid = 'controller.pid';
    private $workerPid = 'worker.pid';
    /**
     * @var AbstractAddress
     */
    private $listenAddress;
    /**
     * @var AbstractAddress
     */
    private $controllerAddress;

    public function __construct(array $config)
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
     * Вернёт путь к pid файлу контроллера.
     *
     * @return string
     */
    public function getControllerPid(): string
    {
        return $this->controllerPid;
    }

    /**
     * Вернёт путь к pid файлу воркера.
     *
     * @return string
     */
    public function getWorkerPid(): string
    {
        return $this->workerPid;
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