<?php

namespace Saw\ValueObject;

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
     * Вернёт PID процесса.
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
     * Вернёт true, если процесс ещё работает.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        $this->update(); // обновляем инфу
        var_dump($this->status);exit;
        return (bool)$this->status['running'];
    }

    /**
     * Убивает процесс с использованием сигнала KILL.
     * Вернёт статус уничтожения процесса.
     * @param int $signal
     * @return bool
     */
    public function kill(int $signal = 9): bool
    {
        return proc_terminate($this->resource, $signal);
    }

    /**
     * Обновляет информацию о состоянии процесса.
     */
    private function update()
    {
        $this->status = proc_get_status($this->resource);
    }
}
