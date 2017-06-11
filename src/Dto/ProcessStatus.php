<?php

namespace Saw\Dto;

/**
 * Обёртка для resource возвращаемого функцией proc_open().
 */
final class ProcessStatus
{
    private $resource;
    private $status;

    public function __construct($processResource)
    {
        if (!is_resource($processResource) || 'process' !== get_resource_type($processResource)) {
            throw new \InvalidArgumentException('Invalid process status resource.');
        }
        $this->resource = $processResource;
        $this->update();
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Получает PID процесса.
     *
     * @return int
     */
    public function getPid(): int
    {
        if (!isset($this->status['pid'])) {
            throw new \RuntimeException('Cannot get pid.');
        }
        return $this->status['pid'];
    }

    /**
     * Обновляет информацию о состоянии процесса.
     */
    private function update()
    {
        $this->status = proc_get_status($this->resource);
    }
}
