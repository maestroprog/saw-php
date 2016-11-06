<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2016
 * Time: 14:50
 */

namespace maestroprog\Saw;


abstract class Application
{
    abstract public function run(Task $task);
}
