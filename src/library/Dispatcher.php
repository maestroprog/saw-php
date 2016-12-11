<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 11:23
 */

namespace maestroprog\saw\library;

use maestroprog\esockets\base\Net;
use maestroprog\saw\entity\Command as EntityCommand;

/**
 * Диспетчер команд.
 */
class Dispatcher extends Singleton
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

    /**
     * @param EntityCommand[] $commands
     * @return Dispatcher
     * @throws \Exception
     */
    public function add(array $commands): self
    {
        foreach ($commands as $command) {
            if (isset($this->know[$command->getName()]) || !class_exists($command->getClass())) {
                throw new \Exception(sprintf('Command %s cannot added', $command->getName()));
            }
            $this->know[$command->getName()] = $command;
        }
        return $this;
    }

    public function create(string $command, Net $client): Command
    {
        static $id = 0;
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command %s', $command));
        }
        $class = $this->know[$command];
        if (!class_exists($class)) {
            throw new \Exception(sprintf('I don\'t know Class %s', $class));
        }
        $this->created[$id] = $command = new $class($id, $client);
        $id++;
        return $command;
    }

    public function dispatch($data, Net $peer)
    {
        $command = $data['command'];
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command %s', $command));
        }
        $commandEntity = $this->know[$command];
        if (isset($this->created[$data['id']])) {
            $command = $this->created[$data['id']];
        } else {
            $class = $commandEntity->getClass();
            $this->created[$data['id']] = $command = new $class($data['id'], $peer, $data['state']);
        }
        /** @var $command Command */
        switch ($command->getState()) {
            case Command::STATE_NEW:
                // why??
                break;
            case Command::STATE_RUN:
                $commandEntity->exec($command);
                break;
            case Command::STATE_RES:
                $command->dispatch();
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
