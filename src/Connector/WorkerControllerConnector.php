<?php

namespace Maestroprog\Saw\Connector;

use Esockets\base\AbstractAddress;
use Esockets\base\exception\ConnectionException;
use Esockets\Client;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\ControllerStarter;

final class WorkerControllerConnector implements ControllerConnectorInterface
{
    use ControllerConnectorTrait;

    private $client;
    private $connectAddress;
    private $commandDispatcher;
    private $controllerStarter;

    public function __construct(
        Client $client,
        AbstractAddress $connectAddress,
        CommandDispatcher $commandDispatcher,
        ControllerStarter $controllerStarter
    )
    {
        $this->client = $client;
        $this->connectAddress = $connectAddress;
        $this->commandDispatcher = $commandDispatcher;
        $this->controllerStarter = $controllerStarter;
    }

    /**
     * Выполняет подключение к контроллеру.
     * Если контроллер не работает, выполняется запуск контроллера.
     *
     * @throws \RuntimeException Если не удалось запустить контроллер
     */
    public function connect()
    {
        try {
            if (!$this->controllerStarter->isExistsPidFile()) {
                throw new ConnectionException('Pid file is not exists.');
            }
            $this->client->connect($this->connectAddress);
        } catch (ConnectionException $e) {
            $this->controllerStarter->start();
        }
//        $this->client->block();
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return CommandDispatcher
     */
    public function getCommandDispatcher(): CommandDispatcher
    {
        return $this->commandDispatcher;
    }

    /*public function work()
    {
//        $this->client->live();
        $this->client->read(); // tmp crutch, costyle costyl
    }*/

    public function send($data): bool
    {
        return $this->client->send($data);
    }
}
