<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 11:23
 */

namespace maestroprog\saw\library;

use maestroprog\esockets\base\Net;
use maestroprog\saw\command\WorkerAdd;

/**
 * Диспетчер команд.
 */
class Dispatcher extends Singleton
{
    /**
     * Команды, которые мы знаем.
     *
     * @var array
     */
    private $know = [];

    /**
     * Созданные команды.
     *
     * @var array
     */
    private $created = [];

    public function add(array $command) : self
    {
        foreach ($command as $name => $class) {
            if (isset($this->know[$name]) || !class_exists($class)) {
                throw new \Exception(sprintf('Command %s cannot added', $name));
            }
            $this->know[$name] = $class;
        }
        return $this;
    }

    public function create(string $command, Net $client) : Command
    {
        static $id = 0;
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command %s', $command));
        }
        $class = $this->know[$command];
        $this->created[$id] = $command = new $class($id, $client);
        $id++;
    }

    public function dispatch($data, Net $peer) : Command
    {
        $command = $data['command'];
        if (!isset($this->know[$command])) {
            throw new \Exception(sprintf('I don\'t know command %s', $command));
        }
        if (isset($this->created[$data['id']])) {
            $command = $this->created[$data['id']];
        } else {
            $class = $this->know[$command];
            $this->created[$data['id']] = $command = new $class($data['id'], $peer, $data['state']);
        }
        return $command;
    }

    public function valid(array &$data)
    {
        return isset($data['command'])
        && isset($data['state'])
        && isset($data['id'])
        && isset($data['data']);
    }
}
