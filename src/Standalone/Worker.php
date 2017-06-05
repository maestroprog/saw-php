<?php

namespace Saw\Standalone;

use Saw\Heading\TaskRunner;
use Saw\Heading\worker\WorkerCore;
use Saw\Command\TaskAdd;
use Saw\Command\TaskRes;
use Saw\Command\TaskRun;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Entity\Task;
use Saw\Heading\dispatcher\Command;
use Saw\Heading\CommandDispatcher;
use Saw\Heading\SawFactory;
use Saw\Heading\Singleton;
use Saw\Heading\Application;
use Saw\Heading\TaskManager;
use Esockets\TcpClient;
use Esockets\debug\Log;
use Saw\Entity\Command as EntityCommand;

/**
 * Воркер, использующийся воркер-скриптом.
 * Используется для выполнения отдельных задач.
 * Работает в качестве демона в нескольких экземплярах.
 */
final class Worker extends Singleton implements TaskRunner
{
    public $work = true;

    public $worker_app;

    public $worker_app_class;

    /**
     * @var TcpClient socket connection
     */
    protected $sc;

    /**
     * @var CommandDispatcher
     */
    protected $dispatcher;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var WorkerCore only for Worker
     */
    protected $core;

    /**
     * Инициализация
     *
     * @param array $config
     * @return bool
     */
    public function init(array &$config): bool
    {
        // настройка сети
        if (isset($config['net'])) {
            $this->sc = new TcpClient($config['net']);

            $this->sc->onRead($this->onRead());

            $this->sc->onDisconnect(function () {
                Log::log('i disconnected!');
                $this->work = false;
            });
        } else {
            trigger_error('Net configuration not found', E_USER_NOTICE);
            return false;
        }
        $this->configure($config);
        if (empty($this->worker_app) || !file_exists($this->worker_app)) {
            throw new \Exception('Worker application configuration not found');
        }
        require_once $this->worker_app;
        if (!class_exists($this->worker_app_class)) {
            throw new \Exception('Worker application must be configured with "worker_app_class"');
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
        $this->core = new WorkerCore($this->sc, $this->worker_app_class);
        $this->dispatcher = SawFactory::getInstance()->createDispatcher([
            new EntityCommand(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (Command $context) {
                    $this->core->run();
                }
            ),
            new EntityCommand(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (Command $context) {
                    $this->stop();
                }
            ),
            new EntityCommand(TaskAdd::NAME, TaskAdd::class),
            new EntityCommand(
                TaskRun::NAME,
                TaskRun::class,
                function (TaskRun $context) {
                    // выполняем задачу
                    $task = new Task($context->getRunId(), $context->getName(), $context->getFromDsc());
                    $this->core->runTask($task);
                }
            ),
            new EntityCommand(
                TaskRes::NAME,
                TaskRes::class,
                function (TaskRes $context) {
                    //todo
                    $this->core->receiveTask(
                        $context->getRunId(),
                        $context->getResult()
                    );
                }
            ),
        ]);
    }

    private function configure(array &$config)
    {
        // настройка доп. параметров
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => &$param) {
                if (property_exists($this, $key)) {
                    $this->$key = $param;
                }
                unset($param);
            }
        }
    }

    public function connect()
    {
        return $this->sc->connect();
    }

    public function stop()
    {
        $this->work = false;
        $this->sc->disconnect();
    }

    public function work()
    {
        $this->sc->setBlock();
        while ($this->work) {
            $this->sc->read();

            if (count($this->core->getRunQueue())) {
                /** @var Task $task */
                $task = array_shift($this->core->getRunQueue());
                $task->setResult($this->core->runCallback($task->getName()));
                $this->dispatcher->create(TaskRes::NAME, $this->sc)
                    ->onError(function () {
                        //todo
                    })
                    ->run(TaskRes::serializeTask($task));
            }

            usleep(INTERVAL);
        }
    }

    public function run()
    {
        $this->core->run();
    }

    /**
     * Метод под нужды таскера - запускает ожидание завершения выполнения указанных в массиве задач.
     *
     * @param Task[] $tasks
     * @return bool
     */
    public function syncTask(array $tasks, float $timeout = 0.1): bool
    {
        $time = microtime(true);
        do {
            $this->sc->read();
            $ok = true;
            foreach ($tasks as $task) {
                if ($task->getState() === Task::ERR) {
                    break;
                }
                if ($task->getState() !== Task::END) {
                    $ok = false;
                }
            }
            if ($ok) {
                break;
            }
            if (microtime(true) - $time > $timeout) {
                // default wait timeout 1 sec
                break;
            }
            usleep(INTERVAL);
        } while (true);

        return $ok;
    }

    public function addTask(Task $task)
    {
        $this->core->addTask($task);
        $this->dispatcher->create(TaskAdd::NAME, $this->sc)
            ->onError(function () use ($task) {
                //todo
                $this->addTask($task); // опять пробуем добавить команду
            })
            ->run(['name' => $task->getName()]);
    }

    /**
     * Настраивает текущий таск-менеджер.
     *
     * @param TaskManager $taskManager
     * @return $this
     */
    public function setTaskManager(TaskManager $taskManager)
    {
        $this->core->setTaskManager($taskManager);
        return $this;
    }

    protected function onRead(): callable
    {
        return function ($data) {
            Log::log('I RECEIVED  :)');
            Log::log(var_export($data, true));

            switch ($data) {
                case 'HELLO':
                    $this->sc->send('HELLO');
                    break;
                case 'ACCEPT':
                    /** временный костыль, т.к. @see Init наследуется от Worker-а */
                    if (SAW_ENVIRONMENT === 'Worker') {
                        $this->dispatcher
                            ->create(WorkerAdd::NAME, $this->sc)
                            ->onError(function () {
                                $this->stop();
                            })
                            ->onSuccess(function () {
                                $this->run();
                            })
                            ->run();
                    } else {
                        $this->run();
                    }
                    break;
                case 'INVALID':
                    // todo
                    break;
                case 'BYE':
                    $this->work = false;
                    break;
                default:
                    if (is_array($data) && $this->dispatcher->valid($data)) {
                        $this->dispatcher->dispatch($data, $this->sc);
                    } else {
                        $this->sc->send('INVALID');
                    }
            }
        };
    }

    /**
     * @param array $config
     * @return Worker
     * @throws \Exception
     */
    public static function create(array $config): Worker
    {
        $init = self::getInstance();
        if ($init->init($config)) {
            Log::log('configured. input...');
            try {
                $init->connect();
                $init->start();
            } catch (\Exception $e) {
                Log::log(sprintf('Worker connect or start failed with error: %s', $e->getMessage()));
                throw new \Exception('Worker starting fail');
            }
            register_shutdown_function(function () use ($init) {
                $init->stop();
                Log::log('closed');
            });
            return $init->setTaskManager(SawFactory::getInstance()->createTaskManager($init));
        } else {
            throw new \Exception('Cannot initialize Worker');
        }
    }
}
