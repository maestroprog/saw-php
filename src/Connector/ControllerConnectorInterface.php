<?php

namespace Maestroprog\Saw\Connector;

use Esockets\base\SenderInterface;
use Esockets\Client;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\CommandDispatcherProviderInterface;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;

interface ControllerConnectorInterface extends SenderInterface, CycleInterface, CommandDispatcherProviderInterface
{

    /**
     * Выполняет подключение к контроллеру.
     * Если контроллер не работает, выполняется запуск контроллера.
     *
     * @throws \RuntimeException Если не удалось запустить контроллер
     */
    public function connect();


    /**
     * @return Client
     */
    public function getClient(): Client;
}
