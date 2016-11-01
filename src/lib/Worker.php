<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:56
 */

namespace maestroprog\Saw;


use maestroprog\esockets\TcpClient;

class Worker extends Singleton
{
    protected static $instance;

    public $work = true;
    /**
     * @var string path to php binaries
     */
    public $php_binary_path = 'php';

    public $controller_path = '.';

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
        return $this->sc->connect();
    }

    public function stop()
    {
        $this->sc->disconnect();
    }

    public function addTask(string $name, &$result)
    {
        $this->sc->send([
            'command' => 'tadd',
        ]);
    }
}
