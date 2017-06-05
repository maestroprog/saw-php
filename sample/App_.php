<?php

class App extends \Saw\Heading\Application
{
    /**
     * @var \Saw\Entity\Task[]
     */
    private $tasks = [];

    public function init()
    {
        // TODO: Implement init() method.
    }

    public function end()
    {
        if ($this->taskManager->sync($this->tasks, 1000)) {

            foreach ($this->tasks as $task) {
                var_dump($task->getResult());
            }
        } else {
            echo 'DIE';
        }
    }

    public function run()
    {
        $this->tasks[] = $this->thread(function () {
            for ($i = 0; $i < 10000000; $i++) {
                'nope';
            }
            return 'i';
        }, 'MODULE_1_INIT');

        $this->tasks[] = $this->thread(function () {
            for ($i = 0; $i < 10000000; $i++) {
                'nope';
            }
            return 'i2';
        }, 'MODULE_2_INIT');

        $this->tasks[] = $this->thread(function () {
            for ($i = 0; $i < 10000000; $i++) {
                'nope';
            }
            return 'i3';
        }, 'MODULE_3_INIT');

        $this->tasks[] = $this->thread(function () {
            for ($i = 0; $i < 10000000; $i++) {
                'nope';
            }
            return 'i4';
        }, 'MODULE_4_INIT');
    }
}