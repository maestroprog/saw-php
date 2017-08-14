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
    const STATE_NEW = 0;
    const STATE_RUN = 1;
    const STATE_RES = 2;

    /**
     * Команды, которые мы знаем.
     *
     * @var CommandHandler[]
     */
    private $know = [];

    /**
     * Созданные команды.
     *
     * @var array
     */
    private $created = [];

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
                throw new \RuntimeException(sprintf('Cannot add handler "%s", handler exists.', $handler->getName()));
            }
            if (!class_exists($handler->getClass())) {
                throw new \RuntimeException(sprintf('Cannot add handler "%s", command class not exists.', $handler->getName()));
            }
            $this->know[$handler->getName()] = $handler;
        }
    }

    /**
     * Создаёт новую команду для постановки задачи.
     *
     * @param string $command
     * @param Client $client
     * @return AbstractCommand
     * @throws \Exception
     *
     * @deprecated
     */
    public function create(string $command, Client $client): AbstractCommand
    {
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command "%s"', $command));
        }
        $class = $this->know[$command]->getClass();
        if (!class_exists($class)) {
            throw new \Exception(sprintf('I don\'t know class "%s"', $class));
        }
        static $id = 0;
        return $this->created[$id] = AbstractCommand::create($class, ++$id, $client);
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
        $command = $data['command'];
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command "%s"', $command));
        }
        $commandEntity = $this->know[$command];
        /** @var $command AbstractCommand */
        if ($data['state'] == self::STATE_RES) {
            if (isset($this->created[$data['id']])) {
                // обрабатываем только созданные задачи
                $command = $this->created[$data['id']];
                $command->reset($data['state'], $data['code']);
            } else {
                // несуществующие залоггируем
                // todo exception
                Log::log(var_export($data, true));
                return;
            }
        } else {
            $class = $commandEntity->getClass();
            $command = AbstractCommand::instance($class, $data['id'], $peer, $data['state'], $data['code']);
        }
        $command->handle($data['data']);
        // смотрим, в каком состоянии находится поступившая к нам команда
        switch ($command->getState()) {
            case self::STATE_NEW:
                throw new \LogicException('Команду даже не запустили!');
                // why??
                // такого состояния не может быть..
                break;
            case self::STATE_RUN:
                // если команда поступила на выполнение -  выполняем
                try {
                    if ($commandEntity->exec($command) !== false) {
                        $command->success();
                    } else {
                        $command->error();
                    }
                } catch (\Throwable $e) {
                    //todo
                    echo $e->getMessage();
                    throw $e;
                }
                break;
            case self::STATE_RES:
                $command->dispatchResult($data['data']);
                unset($this->created[$command->getId()]);
                break;
        }
    }

    public function valid(array &$data)
    {
        return isset($data['command'])
            && isset($data['state'])
            && isset($data['id'])
            && isset($data['data']);
    }
}
