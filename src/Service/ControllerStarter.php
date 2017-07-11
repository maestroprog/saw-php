<?php

namespace Saw\Service;

use Esockets\base\AbstractAddress;
use Esockets\base\exception\ConnectionException;
use Esockets\Client;
use Esockets\debug\Log;

/**
 * Сервис, организующий запуск контроллера.
 */
final class ControllerStarter
{
    private $executor;
    private $client;
    private $connectAddress;
    private $cmd;
    private $pidFile;

    /**
     * ControllerStarter constructor.
     * @param Executor $executor
     * @param Client $client
     * @param AbstractAddress $connectAddress
     * @param string $cmd
     * @param string $pidFile
     */
    public function __construct(
        Executor $executor,
        Client $client,
        AbstractAddress $connectAddress,
        string $cmd,
        string $pidFile
    )
    {
        $this->executor = $executor;
        $this->client = $client;
        $this->connectAddress = $connectAddress;
        $this->cmd = $cmd;
        $this->pidFile = $pidFile;
    }

    /**
     * @throws \RuntimeException
     */
    public function start()
    {
        $before_run = microtime(true);
        $pid = $this->executor->exec($this->cmd);
        $after_run = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        while (true) {
            $try_run = microtime(true);
            try {
                $this->client->connect($this->connectAddress);
                Log::log(sprintf(
                    'run: %f, exec: %f, connected: %f',
                    $before_run,
                    $after_run - $before_run,
                    $try_run - $after_run
                ));
                Log::log('before run time: ' . $before_run);
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
