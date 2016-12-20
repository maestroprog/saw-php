<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\saw\service;

use maestroprog\esockets\TcpClient;
use maestroprog\saw\command\TaskRes;
use maestroprog\saw\command\TaskRun;
use maestroprog\saw\entity\Command;
use maestroprog\saw\entity\Task;
use maestroprog\saw\library\Application;
use maestroprog\saw\library\CommandDispatcher;
use maestroprog\saw\library\Executor;
use maestroprog\esockets\debug\Log;
use maestroprog\saw\library\Factory;
use maestroprog\saw\library\Singleton;
use maestroprog\saw\library\TaskManager;
use maestroprog\saw\library\TaskRunner;
use maestroprog\saw\library\worker\Core;

/**
 * Воркер, использующийся входным скриптом.
 */
final class Init extends Singleton implements TaskRunner
{
    use Executor;

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
     * @var Core only for Worker
     */
    protected $core;

    public $controller_path = 'controller.php';

    /**
     * @var TaskManager
     */
    protected $taskManager;

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

    /**
     * @throws \Exception
     */
    public function start()
    {
        $this->dispatcher = Factory::getInstance()->createDispatcher([
            new Command(TaskRun::NAME, TaskRun::class),
            new Command(
                TaskRes::NAME,
                TaskRes::class,
                function (TaskRes $context) {
                    try {
                        $task = $this->taskManager->getRunTask($context->getRunId());
                        $task->setResult($context->getResult());
                    } catch (\Throwable $e) {
                        $this->stop();
                        throw $e;
                    }
                }
            ),
        ]);
    }

    public function work()
    {
        while ($this->work) {
            $this->sc->read();
            usleep(INTERVAL);
        }
    }

    public function run()
    {
        $this->app = new $this->worker_app_class($this->taskManager);
        $this->app->run();
        $this->app->end();
        $this->stop();
    }


    public function stop()
    {
        $this->work = false;
        $this->sc->disconnect();
    }

    public function addTask(Task $task)
    {
        //throw new \Exception('tmp');
        $this->dispatcher->create(TaskRun::NAME, $this->sc)
            ->onError(function () use ($task) {
                //todo
                $task->setResult($this->taskManager->runCallback($task->getName()));
            })
            ->run(TaskRun::serializeTask($task));
    }

    final public function setTaskManager(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
        return $this;
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
                    $this->run();
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

    public function connect()
    {
        return $this->sc->connect();
    }

    /**
     * @param array $config
     * @return Init|Worker
     * @throws \Exception
     */
    public static function create(array $config): Init
    {
        $init = self::getInstance();
        if (!$init->init($config)) {
            throw new \Exception('Cannot initialize Init worker');
        }
        Log::log('configured. input...');
        try {
            if (!$init->connect()) {
                // controller autostart util
                Log::log('controller starting');
                $before_run = microtime(true);
                $init->exec($init->controller_path);
                Log::log('started');
                $after_run = microtime(true);
                usleep(10000); // await for run controller Saw
                $try = 0;
                while (true) {
                    $try_run = microtime(true);
                    if ($init->connect()) {
                        Log::log(sprintf(
                            'run: %f, exec: %f, connected: %f',
                            $before_run,
                            $after_run - $before_run,
                            $try_run - $after_run
                        ));
                        Log::log('before run time: ' . $before_run);
                        break;
                    }
                    if ($try++ > 10) {
                        throw new \Exception('Attempts were unsuccessfully');
                    }
                    usleep(10000);
                }
            }
            $init->start();
            Log::log('Init started');
            register_shutdown_function(function () use ($init) {
                //$init->stop();//todo
                Log::log('closed');
            });
            return $init->setTaskManager(Factory::getInstance()->createTaskManager($init));
        } catch (\Exception $e) {
            Log::log(sprintf('Saw connect or start failed with error: %s', $e->getMessage()));
            throw new \Exception('Framework starting fail', 0, $e);
        }
    }
}
