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
    const STATE_ACCEPTED = 1;

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
            if ($peer->send('HELLO')) {
                out('sended');
            }
        });
        register_shutdown_function(function () {
            Saw::stop();
        });
        return true;
    }

    public static function work()
    {
        while (self::$work) {
            self::$ss->doAccept();
            self::$ss->doReceive();
            usleep(1000);
        }
    }

    public static function stop()
    {
        self::$ss->close();
        out('closed');
    }
}