<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:44
 */

namespace Saw;


class Saw
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
     * @var Net\Server
     */
    protected static $server;


    /**
     * @var string path to php binaries
     */
    public static $php_binary_path = 'php';

    /**
     * @var Net\Server socket connection
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
            self::$ss = new Net\Server($config['net']);
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
        return self::$ss->open();
    }

    public static function start()
    {
        out('start');
        self::$ss->onAccept(function (Net\Peer &$peer) {
            $peer->set('state', self::STATE_ACCEPTED);
            $peer->onReceive(function (&$data) {
                out('i received! ' . $data);
            });
            $peer->onDisconnect(function () use ($peer) {
                out('peer %% disconnected');
            });
            if ($peer->send('HELLO')) {
                out('sended');
            }
        });
        register_shutdown_function(function () {
            self::stop();
            out('stopped');
        });
        return true;
    }

    public static function work()
    {
        while (self::$work) {
            usleep(INTERVAL);
            self::$ss->doAccept();
            self::$ss->doReceive();
            if (rand(0, 1000) === 500) {
                self::$work = false;
            }
        }
    }

    public static function stop()
    {
        self::$ss->close();
        out('closed');
    }
}