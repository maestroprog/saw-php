<?php

namespace Saw\Heading;

use Saw\Entity\Command;
use Saw\Standalone\Worker;

final class SawFactory extends Singleton
{
    public static function getThreadPool()
    {

    }

    private $dispatcher;

    /**
     * @param Command[] $knowCommands
     * @return CommandDispatcher
     * @throws \Exception
     * @todo @deprecated
     */
    public function createDispatcher(array $knowCommands): CommandDispatcher
    {
        $this->dispatcher or $this->dispatcher = CommandDispatcher::getInstance()->add($knowCommands);
        return $this->dispatcher;
    }

    /**
     * @param TaskRunner $controller
     * @return TaskManager
     * @todo @deprecated
     */
    public function createTaskManager(TaskRunner $controller): TaskManager
    {
        return TaskManager::getInstance()->setController($controller);
    }
}
