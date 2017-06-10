<?php

namespace maestroprog\saw\Service;

use Esockets\base\AbstractAddress;
use Esockets\base\exception\ConnectionException;
use Esockets\Client;
use Saw\Service\Executor;
use Esockets\debug\Log;

/**
 * Сервис, организующий запуск контроллера.
 */
final class ControllerStarter
{
    private $executor;
    private $client;
    private $cmd;
    private $pidFile;

    /**
     * ControllerStarter constructor.
     * @param Executor $executor
     * @param Client $client
     * @param string $cmd
     * @param string $pidFile
     */
    public function __construct(Executor $executor, Client $client, string $cmd, string $pidFile)
    {
        $this->executor = $executor;
        $this->client = $client;
        $this->cmd = $cmd;
        $this->pidFile = $pidFile;
    }

    /**
     * @param AbstractAddress $address
     * @throws \RuntimeException
     */
    public function start(AbstractAddress $address)
    {
        $before_run = microtime(true);
        $pid = $this->executor->exec($this->cmd);
        $after_run = microtime(true);
        if (false === file_put_contents($this->pidFile, $pid)) {
            throw new \RuntimeException('Cannot save the pid in pid file.');
        }
        usleep(10000); // await for run controller Saw
        $try = 0;
        while (true) {
            $try_run = microtime(true);
            try {
                $this->client->connect($address);
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
                    throw new \RuntimeException('Attempts were unsuccessfully');
                }
                usleep(10000);
            }
        }
    }
}
