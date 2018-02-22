<?php

namespace Maestroprog\Saw\ValueObject;

/**
 * Обёртка для resource возвращаемого функцией proc_open().
 */
class ProcessStatus
{
    private $resource;
    private $pipes;
    private $status;

    public function __construct($processResource, array $pipes)
    {
        if (!is_resource($processResource) || 'process' !== get_resource_type($processResource)) {
            throw new \InvalidArgumentException('Invalid process status resource.');
        }
        $this->resource = $processResource;
        $this->pipes = $pipes;
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
        return (bool)$this->status['running'];
    }

    public function input(string $data)
    {
        if (false === fwrite($this->pipes[1], $data)) {
            throw new \RuntimeException('Cannot write to process pipe.');
        }
    }

    public function output(int $length = 1024): string
    {
        return fread($this->pipes[1], $length);
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
