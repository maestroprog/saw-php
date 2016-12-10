<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:56
 */

namespace maestroprog\saw\service;

use maestroprog\library\worker\Core;
use maestroprog\saw\command\WorkerAdd;
use maestroprog\saw\command\WorkerDelete;
use maestroprog\saw\library\Dispatcher;
use maestroprog\saw\library\Factory;
use maestroprog\saw\library\Singleton;
use maestroprog\saw\library\Application;
use maestroprog\saw\library\Task;
use maestroprog\esockets\TcpClient;
use maestroprog\esockets\debug\Log;
use maestroprog\saw\entity\Command;

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
     * @var Task
     */
    private $task;

    private $core;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

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
        $this->core = new Core($this->sc, $this->worker_app, $this->worker_app_class);
        $this->dispatcher = Factory::getInstance()->createDispatcher([
            new Command(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (\maestroprog\saw\library\Command $context) {

                }
            ),
            WorkerDelete::NAME => WorkerDelete::class,
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
     * @param Task $task
     * @return $this
     */
    public function setTask(Task $task)
    {
        $this->task = $task;
        return $this;
    }

    public function run()
    {
        if (!$this->task) {
            throw new \Exception('Cannot run worker!');
        }
        $this->app->run($this->task);
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
                        $this->handle($data);
                    } else {
                        $this->sc->send('INVALID');
                    }
            }
        };
    }

    protected function handle(array $data)
    {
        try {
            $command = $this->dispatcher->dispatch($data, $this->sc);
            $command->handle($data['data']);
            if ($command->getState() === Command::STATE_RUN) {
                switch ($command->getCommand()) {
                    case WorkerAdd::NAME: // add worker

                        break;
                    default:
                        throw new \Exception('Undefined command ' . $command->getCommand());
                }
            } elseif ($command->getState() === Command::STATE_RES) {

            }
        } catch (\Throwable $e) {
            // todo
            return;
        }

        switch ($data['command']) {
            case 'wadd': // successfully add worker to controller
                $this->run();
                break;
            case 'wdel': // controller has been delete this worker from self stack
                $this->stop();
                break;
        }
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
            if (!($init->connect())) {
                Log::log('Worker start failed');
                throw new \Exception('Worker starting fail');
            }
            register_shutdown_function(function () use ($init) {
                $init->stop();
                Log::log('closed');
            });
            return $init->setTask(Factory::getInstance()->createTask($init));
        } else {
            throw new \Exception('Cannot initialize Worker');
        }
    }
}
