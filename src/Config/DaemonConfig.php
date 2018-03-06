<?php

namespace Maestroprog\Saw\Config;

use Esockets\Base\AbstractAddress;

class DaemonConfig
{
    private const CONFIG_MAP = [
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
    /** @var AbstractAddress */
    private $listenAddress;
    /** @var AbstractAddress */
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
     */
    public function getControllerPath(): string
    {
        return $this->controllerPath;
    }

    /**
     * Вернёт путь к исполнимому скрипту воркера.
     */
    public function getWorkerPath(): string
    {
        return $this->workerPath;
    }

    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    public function getInitScriptPath(): string
    {
        return __DIR__ . '/../../bin/cli.php';
    }

    /**
     * Вернёт путь к pid файлу контроллера.
     */
    public function getControllerPid(): string
    {
        return $this->controllerPid;
    }

    public function getListenAddress(): AbstractAddress
    {
        if (!$this->listenAddress instanceof AbstractAddress) {
            throw new \RuntimeException('Listen address is not configured.');
        }
        return $this->listenAddress;
    }

    public function getControllerAddress(): AbstractAddress
    {
        if (!$this->controllerAddress instanceof AbstractAddress) {
            throw new \RuntimeException('Controller address is not configured.');
        }
        return $this->controllerAddress;
    }
}
