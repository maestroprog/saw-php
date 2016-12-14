<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.11.16
 * Time: 16:27
 */

namespace maestroprog\saw\library;

use maestroprog\saw\entity\Command;
use maestroprog\saw\service\Controller;
use maestroprog\saw\service\Worker;
use maestroprog\saw\service\Init;
use maestroprog\esockets\debug\Log;

/**
 * Фабрика воркеров.
 */
class Factory extends Singleton
{
    private $dispatcher;

    /**
     * @param Command[] $knowCommands
     * @return CommandDispatcher
     * @throws \Exception
     */
    public function createDispatcher(array $knowCommands) : CommandDispatcher
    {
        $this->dispatcher or $this->dispatcher = CommandDispatcher::getInstance()->add($knowCommands);
        return $this->dispatcher;
    }

    public function createTaskManager(Worker $controller) : TaskManager
    {
        return TaskManager::getInstance()->setController($controller);
    }
}
