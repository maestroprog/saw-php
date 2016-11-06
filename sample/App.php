<?php

/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2016
 * Time: 16:22
 */
use \maestroprog\Saw\Task;

class App extends \maestroprog\Saw\Application
{
    public function run(Task $task)
    {
        $task->run(function () {
            for ($i = 0; $i < 10000; $i++) {
                'nope';
            }
        }, 'MODULE_1_INIT');

        $task->run(function () {
            for ($i = 0; $i < 10000; $i++) {
                'nope';
            }
        }, 'MODULE_2_INIT');

        $task->run(function () {
            for ($i = 0; $i < 10000; $i++) {
                'nope';
            }
        }, 'MODULE_3_INIT');
    }
}
