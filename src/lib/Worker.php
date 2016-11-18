<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:56
 */

namespace maestroprog\Saw;


use maestroprog\esockets\TcpClient;

/**
 * Воркер, использующийся воркер-скриптом.
 * Используется для выполнения отдельных задач.
 * Работает в качестве демона в нескольких экземплярах.
 */
class Worker extends Singleton
{
    protected static $instance;

    public $work = true;

    /**
     * @var string path to php binaries
     */
    public $php_binary_path = 'php';

    public $controller_path = '.';

    public $worker_app;

    /**
     * @var TcpClient socket connection
     */
    protected $sc;

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
            unset($config);
            return false;
        }
        // настройка доп. параметров
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => &$param) {
                if (isset($this->$key)) $this->$key = $param;
                unset($param);
            }
        }
        unset($config);
        return true;
    }

    public function connect()
    {

        $this->sc->onRead(function ($data) {
            out('I RECEIVED ' . $data . ' :)');
            if ($data === 'HELLO') {
                $this->sc->send('HELLO!');
            } elseif ($data === 'BYE') {
                $this->work = false;
            }
        });

        $this->sc->onDisconnect(function () {
            out('i disconnected!');
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
        return $this->sc->send([
            'command' => 'trun',
            'name' => $name,
        ]);
    }

    public function syncTask(array $names)
    {

    }
}
