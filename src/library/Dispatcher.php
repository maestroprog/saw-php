<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 11:23
 */

namespace maestroprog\saw\library;

use maestroprog\esockets\base\Net;

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
        /**
         * @var $class Command
         */
        $class = $this->know[$command];
        $this->created[$id] = $command = new $class($id, $client);
        $id++;
    }

    public function dispatch($data)
    {

    }

    public function load(string $name)
    {

    }
}
