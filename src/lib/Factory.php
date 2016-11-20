<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.11.16
 * Time: 16:27
 */

namespace maestroprog\Saw;

use maestroprog\esockets\debug\Log;

/**
 * Фабрика воркеров.
 */
class Factory extends Singleton
{
    private $config = [];

    public function configure(array $config) : Factory
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Controller
     * @throws \Exception
     */
    public function createController() : Controller
    {
        $controller = Controller::getInstance();
        if ($controller->init($this->config)) {
            Log::log('configured. start...');
            if (!$controller->start()) {
                Log::log('Saw start failed');
                throw new \Exception('Saw start failed');
            }
            Log::log('start end');
        }
        return $controller;
    }

    /**
     * @return Worker
     * @throws \Exception
     */
    public function createWorker() : Worker
    {
        $init = Worker::getInstance();
        if ($init->init($this->config)) {
            Log::log('configured. input...');
            if (!($init->connect())) {
                Log::log('Worker start failed');
                throw new \Exception('Worker starting fail');
            }
            register_shutdown_function(function () use ($init) {
                $init->stop();
                Log::log('closed');
            });
            return $init->setTask($this->createTask($init));
        } else {
            throw new \Exception('Cannot initialize Worker');
        }
    }

    public function createInput() : Init
    {
        $init = Init::getInstance();
        if ($init->init($this->config)) {
            Log::log('configured. input...');
            if (!($init->connect() or $init->start())) {
                Log::log('Saw start failed');
                throw new \Exception('Framework starting fail');
            }
            register_shutdown_function(function () use ($init) {
                Log::log('work start');
                //$init->work();
                Log::log('work end');

                $init->stop();
                Log::log('closed');
            });
            return $init->setTask($this->createTask($init));
        } else {
            throw new \Exception('Cannot initialize Init worker');
        }
    }

    protected function createTask(Worker $controller) : Task
    {
        return Task::getInstance()->setController($controller);
    }
}
