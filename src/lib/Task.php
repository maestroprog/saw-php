<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:14
 */

namespace maestroprog\Saw;


class Task extends Singleton
{
    /**
     * @var Worker|Init
     */
    protected $controller;

    public function setController($controller)
    {
        if ($controller instanceof Init || $controller instanceof Worker) {
            $this->controller = $controller;
            return true;
        }
        return false;
    }

    public static function run(callable $task, string $name, &$result = null)
    {
        if (self::getInstance()->controller->addTask($name, $result)) {
            // можно спокойно выходить отсюда :)
        } else {
            $result = $task();
        }
    }
}
