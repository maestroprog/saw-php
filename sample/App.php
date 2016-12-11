<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2016
 * Time: 16:22
 */

use \maestroprog\saw\library\Task;

class App extends \maestroprog\saw\library\Application
{
    public function init(array $_SERVER)
    {
        // TODO: Implement init() method.
    }

    public function end()
    {
        // TODO: Implement end() method.
    }

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
