<?php

namespace Saw;

use Esockets\base\Configurator;
use Esockets\Client;
use maestroprog\saw\Service\ControllerStarter;
use Saw\Command\CommandHandler;
use Saw\Config\DaemonConfig;
use Saw\Service\CommandDispatcher;
use Saw\Service\Executor;

/**
 * Фабрика всех сервисов и обхектов для пилы.
 */
final class SawFactory
{
    const CALL_POINTER = '!';

    private $config;
    private $daemonConfig;
    private $socketConfigurator;

    private $controllerClient;

    private $dispatcher;
    private $executor;

    public function __construct(
        array $config,
        DaemonConfig $daemonConfig,
        Configurator $socketConfigurator
    )
    {
        if (!isset($config['starter'])) {
            $config['starter'] = '-r "require \'bootstrap.php\'"';
        }
        $this->config = $config;
        $this->daemonConfig = $daemonConfig;
        $this->socketConfigurator = $socketConfigurator;
    }

    public function instanceArguments(array $arguments): array
    {
        array_walk($arguments, function (&$argument) {
            if (is_array($argument)) {
                $arguments = [];
                if (isset($argument['arguments'])) {
                    $arguments = $this->instanceArguments($argument['arguments']);
                }
                if (isset($argument['method'])) {
                    $argument = call_user_func([$this, $argument['method']]);
                } else {
                    $argument = $arguments;
                }
            } elseif (self::CALL_POINTER === substr($argument, 0, 1)) {
                $argument = call_user_func([$this, substr($argument, 0, -1)]);
            }
        });
        return $arguments;
    }

    public function getControllerClient(): Client
    {
        $this->controllerClient or $this->controllerClient = $this->socketConfigurator->makeClient();
        return $this->controllerClient;
    }

    public function getControllerStarter(): ControllerStarter
    {
        return new ControllerStarter(
            $this->getExecutor(),
            $this->controllerClient,
            $this->config['starter']
        );
    }

    public function getWebThreadRunner()
    {

    }

    public function getExecutor(): Executor
    {
        if (is_null($this->executor)) {
            $phpPath = null;
            if (isset($this->config['executor'])) {
                $phpPath = $this->config['executor'];
            }
            $this->executor = new Executor($phpPath);
        }
        return $this->executor;
    }

    /**
     * @param Client $client
     * @param CommandHandler[] $knowCommands
     * @return CommandDispatcher
     * @throws \Exception
     */
    public function createCommandDispatcher(Client $client, array $knowCommands): CommandDispatcher
    {
        return $this->dispatcher
            ?? $this->dispatcher = (new CommandDispatcher($client))->add($knowCommands);
    }
}
