<?php

namespace Saw\Config;

final class ControllerConfig
{
    /**
     * ??
     *
     * @var int
     */
    private $workerMultiplier;

    /**
     * Максимальное количество воркеров.
     *
     * @var int
     */
    private $workerMaxCount;

    public function __construct(array $config)
    {
        $this->workerMultiplier = $config['worker_multiplier'] ?? 4;
        $this->workerMaxCount = $config['worker_max_count'] ?? 4;
    }
}
