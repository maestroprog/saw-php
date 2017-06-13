<?php

namespace Saw\Config;

final class ControllerConfig
{
    private $workerMultiplier;
    private $workerMaxCount;

    public function __construct(array $config)
    {
        $this->workerMultiplier = $config['worker_multiplier'] ?? 4;
        $this->workerMaxCount = $config['worker_max_count'] ?? 4;
    }

    /**
     * @return int
     */
    public function getWorkerMultiplier(): int
    {
        return $this->workerMultiplier;
    }

    /**
     * Максимальное количество воркеров.
     *
     * @return int
     */
    public function getWorkerMaxCount(): int
    {
        return $this->workerMaxCount;
    }
}
