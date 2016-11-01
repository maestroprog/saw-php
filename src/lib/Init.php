<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\Saw;

use maestroprog\esockets\TcpClient;


class Init extends Worker
{
    public $work = true;
    /**
     * @var string path to php binaries
     */
    public $php_binary_path = 'php';

    public $controller_path = '.';

    /**
     * @var TcpClient socket connection
     */
    private $sc;

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

    public function start()
    {
        out('starting');
        $before_run = microtime(true);
        $this->exec($this->controller_path . DIRECTORY_SEPARATOR . 'controller.php');
        out('started');
        $after_run = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        do {
            $try_run = microtime(true);
            #usleep(100000);
            if ($this->connect()) {
                out(sprintf('run: %f, exec: %f, connected: %f', $before_run, $after_run - $before_run, $try_run - $after_run));
                out('before run time: ' . $before_run);
                return true;
            }
            usleep(10000);
        } while ($try++ < 10);
        return false;
    }

    public function work()
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

        while ($this->work) {
            usleep(INTERVAL);
            $this->sc->read();
        }
    }

    public function stop()
    {
        $this->sc->disconnect();
    }

    private function exec($cmd)
    {
        $cmd = sprintf('%s -f %s', $this->php_binary_path, $cmd);
        if (PHP_OS === "WINNT") {
            $cmd = str_replace('\\', '\\\\', $cmd);
            pclose(popen($e = "start /B " . $cmd, "r"));
        } else {
            exec($e = $cmd . " > /dev/null 2>&1 &");
        }
        out($e);
    }

    private function kill()
    {
        // todo
    }
}
