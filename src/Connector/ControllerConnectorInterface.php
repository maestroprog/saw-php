<?php

namespace Maestroprog\Saw\Connector;

use Esockets\Base\SenderInterface;
use Esockets\Client;
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
    public function connect(): void;


    /**
     * @return Client
     */
    public function getClient(): Client;

    /**
     * Выполняет полезную работу по взаимодействию между контроллерами и воркерами.
     *
     * @return \Generator
     */
    public function work(): \Generator;
}
