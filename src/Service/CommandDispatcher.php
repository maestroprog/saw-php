<?php

namespace Maestroprog\Saw\Service;

use Esockets\Client;
use Esockets\debug\Log;
use Maestroprog\Saw\Command\AbstractCommand;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ContainerOfCommands;

/**
 * Диспетчер команд.
 */
final class CommandDispatcher
{
    const STATE_NEW = 0; // todo remove?
    const STATE_RUN = 1;
    const STATE_RES = 2;

    const CODE_VOID = 0;
    const CODE_SUCCESS = 1;
    const CODE_ERROR = 2;

    /**
     * Команды, которые мы знаем.
     *
     * @var CommandHandler[]
     */
    private $know = [];

    private $runCommands;

    public function __construct(ContainerOfCommands $runCommands)
    {
        $this->runCommands = $runCommands;
    }

    /**
     * Добавляет новую команду в список известных команд.
     *
     * @param CommandHandler[] $handlers
     * @return void
     * @throws \RuntimeException
     */
    public function addHandlers(array $handlers)
    {
        foreach ($handlers as $handler) {
            if (isset($this->know[$handler->getName()])) {
                throw new \RuntimeException(sprintf(
                    'Cannot add handler "%s", handler exists.',
                    $handler->getName()
                ));
            }
            if (!class_exists($handler->getClass())) {
                throw new \RuntimeException(sprintf(
                    'Cannot add handler "%s", command class not exists.',
                    $handler->getName()
                ));
            }
            $this->know[$handler->getName()] = $handler;
        }
    }

    /**
     * Обрабатывает поступившую команду.
     *
     * @param $data
     * @param Client $peer
     * @throws \Exception
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function dispatch($data, Client $peer)
    {
        if ($data['state'] === self::STATE_RES) {
            $this->runCommands->get($data['id'])->dispatchResult($data['data'], $data['code']);
        } else {
            $command = $data['command'];
            if (!isset($this->know[$command])) {
                throw new \Exception(sprintf('I don\'t know command "%s"', $command));
            }
            $commandEntity = $this->know[$command];
            if (!AbstractCommand::isValidClass($class = $commandEntity->getClass())) {
                throw new \InvalidArgumentException('Invalid command class.');
            }

            /** @var AbstractCommand $class */
            /** @var $command AbstractCommand */
            if ($data['state'] != self::STATE_RUN) {
                // todo разгрести
                // why??
                // такого состояния не может быть..
                throw new \LogicException('Команду даже не запустили!');
            }

            // если команда поступила на выполнение -  выполняем
            $command = $class::fromArray($data['data'], $peer);
            try {
                $data['data'] = $commandEntity->exec($command);
                $data['code'] = self::CODE_SUCCESS;
            } catch (\Throwable $e) {
                // todo рефакторинг
                $data['data'] = serialize($e);
                $data['code'] = self::CODE_ERROR;
            } finally {
                $data['state'] = self::STATE_RES;
                $peer->send($data);
            }
        }
    }

    public function valid(array &$data)
    {
        return isset($data['command'])
            && isset($data['state'])
            && isset($data['id'])
            && array_key_exists('data', $data);
    }
}
