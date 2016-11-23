<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:56
 */

namespace maestroprog\Saw;


use maestroprog\esockets\TcpClient;
use maestroprog\esockets\debug\Log;

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
        } else {
            trigger_error('Net configuration not found', E_USER_NOTICE);
            return false;
        }
        // настройка доп. параметров
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => &$param) {
                if (property_exists($this, $key)) {
                    $this->$key = $param;
                }
                unset($param);
            }
        }
        if (empty($this->worker_app) || !file_exists($this->worker_app)) {
            trigger_error('Worker application configuration not found', E_USER_ERROR);
            return false;
        }
        require_once $this->worker_app;
        $this->app = new $this->worker_app_class();
        if (!$this->app instanceof Application) {
            trigger_error('Worker application must be instance of maestroprog\saw\Application', E_USER_ERROR);
            return false;
        }
        return true;
    }

    public function connect()
    {
        $this->sc->onRead(function ($data) {
            Log::log('I RECEIVED ' . $data . ' :)');
            if ($data === 'HELLO') {
                $this->sc->send('HELLO!');
            } elseif ($data === 'BYE') {
                $this->work = false;
            }
        });

        $this->sc->onDisconnect(function () {
            Log::log('i disconnected!');
            $this->work = false;
        });

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
            usleep(INTERVAL);
            $this->sc->read();
        }
    }

    /**
     * @var array
     */
    private $knowCommands = [];

    public function addTask(callable &$callback, string $name, &$result)
    {
        if (!isset($this->knowCommands[$name])) {
            $this->knowCommands[$name] = [$callback, &$result];
            $this->sc->send([
                'command' => 'tadd',
                'name' => $name,
            ]);
        }
    }

    public function syncTask(array $names)
    {

    }

    /**
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
        $this->app->run($this->task);
    }
}
