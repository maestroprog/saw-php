<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:56
 */

namespace maestroprog\saw\service;

use maestroprog\library\worker\Core;
use maestroprog\saw\command\TaskAdd;
use maestroprog\saw\command\TaskRun;
use maestroprog\saw\command\WorkerAdd;
use maestroprog\saw\command\WorkerDelete;
use maestroprog\saw\library\Command;
use maestroprog\saw\library\CommandDispatcher;
use maestroprog\saw\library\Factory;
use maestroprog\saw\library\Singleton;
use maestroprog\saw\library\Application;
use maestroprog\saw\library\TaskManager;
use maestroprog\esockets\TcpClient;
use maestroprog\esockets\debug\Log;
use maestroprog\saw\entity\Command as EntityCommand;

/**
 * Воркер, использующийся воркер-скриптом.
 * Используется для выполнения отдельных задач.
 * Работает в качестве демона в нескольких экземплярах.
 */
class Worker extends Singleton
{
    protected static $instance;

    public $work = true;

    public $worker_app;

    public $worker_app_class;

    /**
     * @var TcpClient socket connection
     */
    protected $sc;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var TaskManager
     */
    private $taskManager;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var CommandDispatcher
     */
    protected $dispatcher;

    /**
     * Инициализация
     *
     * @param array $config
     * @return bool
     */
    public function init(array &$config)
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
        return true;
    }

    /**
     * Запускаем воркер в отдельном методе.
     */
    public function start()
    {
        $this->core = new Core($this->sc, $this->worker_app, $this->worker_app_class);
        $this->dispatcher = Factory::getInstance()->createDispatcher([
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
            new EntityCommand(
                TaskRun::NAME,
                TaskRun::class,
                function (Command $context) {
                    $data = $context->getData();
                    $this->core->runTask(
                        $data['callback'],
                        $data['name'],
                        $data['result']
                    );
                }
            ),
        ]);
        return true;
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
        while ($this->work) {
            $this->sc->read();
            usleep(INTERVAL);
        }
    }

    /**
     * Добавляет задачу на выполнение.
     *
     * @param callable $task
     * @param string $name
     * @param $result
     */
    public function addTask(callable &$task, string $name, &$result)
    {
        $this->core->addTask($task, $name, $result);
        $this->dispatcher->create(TaskAdd::NAME, $this->sc)
            ->setError(function () use (&$task, $name, &$result) {
                $this->addTask($task, $name, $result); // опять пробуем добавить команду
            })
            ->run();
    }

    /**
     * Метод под нужды таскера - запускает ожидание завершения выполнения указанных в массиве задач.
     *
     * @param array $names
     */
    public function syncTask(array $names)
    {

    }

    /**
     * Настраивает текущий таск-менеджер.
     *
     * @param TaskManager $taskManager
     * @return $this
     */
    public function setTask(TaskManager $taskManager)
    {
        $this->core->setTaskManager($taskManager);
        return $this;
    }

    protected function onRead(): callable
    {
        return function ($data) {
            Log::log('I RECEIVED ' . $data . ' :)');

            switch ($data) {
                case 'HELLO':
                    $this->sc->send('HELLO');
                    break;
                case 'ACCEPT':
                    $this->dispatcher
                        ->create(WorkerAdd::NAME, $this->sc)
                        ->setError(function () {
                            $this->stop();
                        })
                        ->setSuccess(function () {
                            //todo
                        })
                        ->run();
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
        $init = Worker::getInstance();
        if ($init->init($config)) {
            Log::log('configured. input...');
            if (!($init->connect() && $init->start())) {
                Log::log('Worker connect failed');
                throw new \Exception('Worker starting fail');
            }
            register_shutdown_function(function () use ($init) {
                $init->stop();
                Log::log('closed');
            });
            return $init->setTask(Factory::getInstance()->createTaskManager($init));
        } else {
            throw new \Exception('Cannot initialize Worker');
        }
    }
}
