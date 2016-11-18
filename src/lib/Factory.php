<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.11.16
 * Time: 16:27
 */

namespace maestroprog\Saw;

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
     */
    public function createController() : Controller
    {
        define('SAW_ENVIRONMENT', 'Controller');

        $controller = Controller::getInstance();
        if ($controller->init($config)) {
            out('configured. start...');
            $controller->start() or (out('Saw start failed') or exit);
            out('start end');
        }
        return $controller;
    }

    public function createWorker() : Task
    {
        $init = Worker::getInstance();
        if ($init->init($config)) {
            out('configured. input...');
            if (!($init->connect())) {
                out('Worker start failed');
                throw new \Exception('Worker starting fail');
            }
            register_shutdown_function(function () use ($init) {
                out('work start');
                //$init->work();
                out('work end');

                $init->stop();
                out('closed');
            });
            return $this->createTask($init);
        } else {
            throw new \Exception('Cannot initialize Worker');
        }
    }

    public function createInput() : Task
    {
        define('SAW_ENVIRONMENT', 'Input');

        $init = Init::getInstance();
        if ($init->init($config)) {
            out('configured. input...');
            if (!($init->connect() or $init->start())) {
                out('Saw start failed');
                throw new \Exception('Framework starting fail');
            }
            register_shutdown_function(function () use ($init) {
                out('work start');
                //$init->work();
                out('work end');

                $init->stop();
                out('closed');
            });
            return $this->createTask($init);
        } else {
            throw new \Exception('Cannot initialize Init worker');
        }
    }

    protected function createTask(Worker $controller) : Task
    {
        return Task::getInstance()->setController($controller);
    }
}
