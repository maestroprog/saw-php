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
     * @throws \Exception
     */
    public function createController() : Controller
    {
        $controller = Controller::getInstance();
        if ($controller->init($config)) {
            fputs(STDERR, 'configured. start...');
            if (!$controller->start()) {
                fputs(STDERR, 'Saw start failed');
                throw new \Exception('Saw start failed');
            }
            fputs(STDERR, 'start end');
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
            fputs(STDERR, 'configured. input...');
            if (!($init->connect())) {
                fputs(STDERR, 'Worker start failed');
                throw new \Exception('Worker starting fail');
            }
            register_shutdown_function(function () use ($init) {
                $init->stop();
                fputs(STDERR, 'closed');
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
            fputs(STDERR, 'configured. input...');
            if (!($init->connect() or $init->start())) {
                fputs(STDERR, 'Saw start failed');
                throw new \Exception('Framework starting fail');
            }
            register_shutdown_function(function () use ($init) {
                fputs(STDERR, 'work start');
                //$init->work();
                fputs(STDERR, 'work end');

                $init->stop();
                fputs(STDERR, 'closed');
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
