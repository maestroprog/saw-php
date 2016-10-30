<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\Saw;

use maestroprog\esockets\TcpClient;


class Init
{
    public static $work = true;
    /**
     * @var string path to php binaries
     */
    public static $php_binary_path = 'php';

    public static $controller_path = '.';

    /**
     * @var TcpClient socket connection
     */
    private static $sc;

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
            self::$sc = new TcpClient($config['net']);
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

    public static function connect()
    {
        return self::$sc->connect();
    }

    public static function start()
    {
        out('starting');
        $before_run = microtime(true);
        exec($e = self::$php_binary_path . ' -f ' . self::$controller_path . '/controller.php > /dev/null &');
        out($e);
        out('started');
        $after_run = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        do {
            $try_run = microtime(true);
            #usleep(100000);
            usleep(10000);
            if (self::connect()) {
                out(sprintf('run: %f, exec: %f, connected: %f', $before_run, $after_run - $before_run, $try_run - $after_run));
                out('before run time: ' . $before_run);
                return true;
            }
        } while ($try++ < 10);
        return false;
    }

    public static function work()
    {
        self::$sc->onRead(function (&$data) {
            out('I RECEIVED ' . $data . ' :)');
            self::$sc->send('HELLO!');
        });

        self::$sc->onDisconnect(function () {
            out('i disconnected!');
            self::$work = false;
        });

        while (self::$work) {
            usleep(INTERVAL);
            self::$sc->read();
            self::$sc->send('HELLO WORK!');
        }
    }

    public static function stop()
    {
        self::$sc->disconnect();
    }
}
