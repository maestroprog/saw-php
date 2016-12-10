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
     * @return Dispatcher
     * @throws \Exception
     */
    public function createDispatcher(array $knowCommands) : Dispatcher
    {
        $this->dispatcher or $this->dispatcher = Dispatcher::getInstance()->add($knowCommands);
        return $this->dispatcher;
    }

    public function createTask(Worker $controller) : Task
    {
        return Task::getInstance()->setController($controller);
    }
}
