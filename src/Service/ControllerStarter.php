<?php

namespace Maestroprog\Saw\Service;

use Esockets\Base\AbstractAddress;
use Esockets\Base\Exception\ConnectionException;
use Esockets\Client;
use Esockets\Debug\Log;

/**
 * Сервис, организующий запуск контроллера.
 */
final class ControllerStarter
{
    private $runner;
    private $client;
    private $connectAddress;
    private $pidFile;

    public function __construct(
        ControllerRunner $runner,
        Client $client,
        AbstractAddress $connectAddress,
        string $pidFile
    )
    {
        $this->runner = $runner;
        $this->client = $client;
        $this->connectAddress = $connectAddress;
        $this->pidFile = $pidFile;
    }

    /**
     * @throws \RuntimeException
     */
    public function start(): void
    {
        $beforeRun = microtime(true);
        $pid = $this->runner->start();
        $afterRun = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        while (true) {
            $tryRun = microtime(true);
            try {
                $this->client->connect($this->connectAddress);
                Log::log(sprintf(
                    'run: %f, exec: %f, connected: %f',
                    $beforeRun,
                    $afterRun - $beforeRun,
                    $tryRun - $afterRun
                ));
                Log::log('before run time: ' . $beforeRun);
                break;
            } catch (ConnectionException $e) {
                if ($try++ > 10) {
                    if ($this->isExistsPidFile()) {
                        unlink($this->pidFile);
                    }
                    throw new \RuntimeException('Attempts were unsuccessfully');
                }
                usleep(10000);
            }
        }
    }

    public function isExistsPidFile(): bool
    {
        return file_exists($this->pidFile);
    }
}
