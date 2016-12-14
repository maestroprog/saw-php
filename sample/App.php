<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 05.11.2016
 * Time: 16:22
 */

use \maestroprog\saw\library\TaskManager;

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

    public function run(TaskManager $taskManager)
    {
        $task1 = $taskManager->run(function () {
            for ($i = 0; $i < 10000; $i++) {
                'nope';
            }
        }, 'MODULE_1_INIT');

        $task2 = $taskManager->run(function () {
            for ($i = 0; $i < 10000; $i++) {
                'nope';
            }
        }, 'MODULE_2_INIT');

        $task3 = $taskManager->run(function () {
            for ($i = 0; $i < 10000; $i++) {
                'nope';
            }
        }, 'MODULE_3_INIT');

        $taskManager->sync([
            $task1, $task2, $task3
        ]);
    }
}
