<?php

namespace Saw\Service;

use Esockets\Client;
use Saw\Command\AbstractCommand;
use Saw\Command\CommandHandler as EntityCommand;

/**
 * Диспетчер команд.
 */
final class CommandDispatcher
{
    /**
     * Команды, которые мы знаем.
     *
     * @var EntityCommand[]
     */
    private $know = [];

    /**
     * Созданные команды.
     *
     * @var array
     */
    private $created = [];

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Добавляет новую команду в список известных команд.
     *
     * @param EntityCommand[] $commands
     * @return CommandDispatcher
     * @throws \Exception
     */
    public function add(array $commands): self
    {
        foreach ($commands as $command) {
            if (isset($this->know[$command->getName()]) || !class_exists($command->getClass())) {
                throw new \Exception(sprintf('AbstractCommand %s cannot added', $command->getName()));
            }
            $this->know[$command->getName()] = $command;
        }
        return $this;
    }

    /**
     * Создаёт новую команду для постановки задачи.
     *
     * @param string $command
     * @return AbstractCommand
     * @throws \Exception
     */
    public function create(string $command): AbstractCommand
    {
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command %s', $command));
        }
        $class = $this->know[$command]->getClass();
        if (!class_exists($class)) {
            throw new \Exception(sprintf('I don\'t know Class %s', $class));
        }
        static $id = 0;
        $this->created[$id] = $command = new $class($id, $this->client);
        $id++;
        return $command;
    }

    /**
     * Обрабатывает поступившую команду.
     *
     * @param $data
     * @param Client $peer
     * @throws \Exception
     */
    public function dispatch($data, Client $peer)
    {
        $command = $data['command'];
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command %s', $command));
        }
        $commandEntity = $this->know[$command];
        /** @var $command AbstractCommand */
        if ($data['state'] == AbstractCommand::STATE_RES) {
            $command = $this->created[$data['id']];
            $command->reset($data['state'], $data['code']);
        } else {
            $class = $commandEntity->getClass();
            $command = new $class($data['id'], $peer, $data['state'], $data['code']);
        }
        $command->handle($data['data']);
        // смотрим, в каком состоянии находится поступившая к нам команда
        switch ($command->getState()) {
            case AbstractCommand::STATE_NEW:
                throw new \RuntimeException('Команду даже не запустили!');
                // why??
                // такого состояния не может быть..
                break;
            case AbstractCommand::STATE_RUN:
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
                    exit;
                }
                break;
            case AbstractCommand::STATE_RES:
                $command->dispatch($data['data']);
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

    public function remember(int $commandId): AbstractCommand
    {
        return $this->created[$commandId] ?? null;
    }
}
