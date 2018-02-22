<?php

namespace Maestroprog\Saw\Connector;

use Esockets\Base\AbstractAddress;
use Esockets\Base\Exception\ConnectionException;
use Esockets\Client;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\ControllerStarter;

/**
 * Коннектор, использующийся index.php скриптом, для подключения к контроллеру.
 */
final class WebControllerConnector implements ControllerConnectorInterface
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

        $client->onReceive($this->onRead());
        $this->connect();
    }

    protected function onRead(): callable
    {
        return function ($data) {

            switch ($data) {
                case 'ACCEPT':
                    // todo
                    break;
                case 'INVALID':
                    // todo
                    break;
                case 'BYE':

                    break;
                default:
                    if (is_array($data) && $this->commandDispatcher->valid($data)) {
                        $this->commandDispatcher->dispatch($data, $this->client);
                    } else {
                        $this->client->send('INVALID');
                    }
            }
        };
    }

    /**
     * Выполняет подключение к контроллеру.
     * Если контроллер не работает, выполняется запуск контроллера.
     *
     * @throws \RuntimeException Если не удалось запустить контроллер
     */
    public function connect(): void
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

    public function send($data): bool
    {
        return $this->client->send($data);
    }
}
