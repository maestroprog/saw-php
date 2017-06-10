<?php

namespace Saw\Config;


class DaemonConfig
{
    const CONFIG_MAP = [
        'controller_path' => 'controllerPath',
        'worker_path' => 'workerPath',
        'controller_pid' => 'controllerPid',
        'worker_pid' => 'workerPid',
    ];
    private $controllerPath;
    private $workerPath;
    private $controllerPid = 'controller.pid';
    private $workerPid = 'worker.pid';

    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$$key} = $value;
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

    public function getControllerPath(): string
    {
        return $this->controllerPath;
    }

    public function getWorkerPath(): string
    {
        return $this->workerPath;
    }

    public function getControllerPid(): string
    {
        return $this->controllerPid;
    }

    public function getWorkerPid(): string
    {
        return $this->workerPid;
    }
}
