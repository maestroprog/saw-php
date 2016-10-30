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
    public static $work = true;

    /**
     * @var bool вызывать pcntl_dispatch_signals()
     */
    public static $dispatch_signals = false;

    /**
     * @var TcpServer
     */
    protected static $server;


    /**
     * @var string path to php binaries
     */
    public static $php_binary_path = 'php';

    /**
     * @var TcpServer socket connection
     */
    private static $ss;

    /**
     * Инициализация
     *
     * @param array $config
     * @return bool
     */
    public static function init(array &$config)
    {
        // настройка сети
        if (isset($config['net'])) {
            self::$ss = new TcpServer($config['net']);
        } else {
            trigger_error('Net configuration not found', E_USER_NOTICE);
            unset($config);
            return false;
        }
        // настройка доп. параметров
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => &$param) {
                if (isset(self::$$key)) self::$$key = $param;
                unset($param);
            }
        }
        unset($config);
        return true;
    }

    public static function open()
    {
        return self::$ss->connect();
    }

    public static function start()
    {
        out('start');
        self::$ss->onConnectPeer(function (Peer &$peer) {
            $peer->set('state', self::STATE_ACCEPTED);
            $peer->onRead(function (&$data) use ($peer) {
                switch ($data['command']) {
                    case 'wadd': // add worker
                    case 'wdel': // del worker
                    case 'tadd': // add new task

                }
            });
            $peer->onDisconnect(function () use ($peer) {
                out('peer %% disconnected');
            });
            if (!$peer->send('HELLO')) {
                out('HELLO FAIL SEND!');
            }
        });
        register_shutdown_function(function () {
            self::stop();
            out('stopped');
        });
        return true;
    }

    private $workers = [];

    public static function work()
    {
        while (self::$work) {
            usleep(INTERVAL);
            self::$ss->listen();
            self::$ss->read();
            if (self::$dispatch_signals) {
                pcntl_signal_dispatch();
            }
        }
    }

    public static function stop()
    {
        self::$work = false;
        self::$ss->disconnect();
        out('closed');
    }
}
