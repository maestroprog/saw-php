<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.11.16
 * Time: 16:27
 */

namespace maestroprog\saw\Heading;

use maestroprog\saw\entity\Command;
use maestroprog\saw\Standalone\Worker;

/**
 * Фабрика воркеров.
 */
final class Factory extends Singleton
{
    private $dispatcher;

    /**
     * @param Command[] $knowCommands
     * @return CommandDispatcher
     * @throws \Exception
     */
    public function createDispatcher(array $knowCommands): CommandDispatcher
    {
        $this->dispatcher or $this->dispatcher = CommandDispatcher::getInstance()->add($knowCommands);
        return $this->dispatcher;
    }

    public function createTaskManager(TaskRunner $controller): TaskManager
    {
        return TaskManager::getInstance()->setController($controller);
    }
}
