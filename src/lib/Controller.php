<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:44
 */

namespace maestroprog\Saw;

use maestroprog\esockets\Peer;
use maestroprog\esockets\TcpServer;

class Controller extends Singleton
{
    /**
     * Константы возможных типов подключающихся клиентов
     */
    const CLIENT_INPUT = 1; // input-клиент, передающий запрос
    const CLIENT_WS_INPUT = 2; // WS input-клиент, передающий запрос (зарезервировано)
    const CLIENT_WORKER = 3; // воркер
    const CLIENT_CONTROLLER = 4; // контроллер. (зарезервировано)
    const CLIENT_DEBUG = 5; // отладчик
    /**
     * Константы возможных состояний подключения с клиентом
     */
    const STATE_ACCEPTED = 1; // соединение принято

    /**
     * @var bool
     */
    public $work = true;

    /**
     * @var bool вызывать pcntl_dispatch_signals()
     */
    public $dispatch_signals = false;

    /**
     * @var TcpServer
     */
    protected $server;


    /**
     * @var string path to php binaries
     */
    public $php_binary_path = 'php';

    /**
     * @var TcpServer socket connection
     */
    private $ss;

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
            $this->ss = new TcpServer($config['net']);
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

    public function start()
    {
        if (!$this->ss->connect()) {
            throw new \Exception('Cannot start: not connected');
        }
        out('start');
        $this->ss->onConnectPeer(function (Peer $peer) {
            out('peer connected' . $peer->getAddress());
            $peer->set('state', self::STATE_ACCEPTED);
            $peer->onRead(function ($data) use ($peer) {
                switch ($data['command']) {
                    case 'wadd': // add worker
                    case 'wdel': // del worker
                    case 'tadd': // add new task
                    case 'trun': // run task (name)
                }
            });
            $peer->onDisconnect(function () use ($peer) {
                out('peer disconnected');
            });
            if (!$peer->send('HELLO')) {
                out('HELLO FAIL SEND!');
            }
        });
        register_shutdown_function(function () {
            $this->stop();
            out('stopped');
        });
        return true;
    }

    private $workers = [];

    public function work()
    {
        while ($this->work) {
            usleep(INTERVAL);
            $this->ss->listen();
            $this->ss->read();
            if ($this->dispatch_signals) {
                pcntl_signal_dispatch();
            }
        }
    }

    public function stop()
    {
        $this->work = false;
        $this->ss->disconnect();
        out('closed');
    }
}
