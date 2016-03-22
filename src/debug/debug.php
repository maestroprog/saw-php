<?php
/**
 * Created by PhpStorm.
 * User: ������
 * Date: 05.11.2015
 * Time: 17:02
 */
namespace Saw {

    $common_dir = realpath(__DIR__ . '/../common') . '/';

    require_once $common_dir . 'Net.php';
    require_once $common_dir . 'Client.php';

    unset($common_dir);

    class SawInit
    {
        public static $work = true;
        /**
         * @var string path to php binaries
         */
        public static $php_binary_path = 'php';

        public static $controller_path = '.';

        /**
         * @var Net\Client socket connection
         */
        private static $sc;

        /**
         * �������������
         *
         * @param array $config
         * @return bool
         */
        public static function init(array &$config)
        {
            // ��������� ����
            if (isset($config['net'])) {
                self::$sc = new Net\Client($config['net']);
            } else {
                trigger_error('Net configuration not found', E_USER_NOTICE);
                unset($config);
                return false;
            }
            // ��������� ���. ����������
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
            self::$sc->onReceive(function (&$data) {
                out('I RECEIVED ' . $data . ' :)');
                self::$sc->send('HELLO!');
            });

            while (self::$work) {
                usleep(1000);
                self::$sc->doReceive();
            }
            return true;
        }

        public static function stop()
        {
            self::$sc->doDisconnect();
        }
    }
}

namespace {

    use Saw\SawInit;

    define('SAW_ENVIRONMENT', 'DEBUG');
    $config = require __DIR__ . '/../config.php';

    try {
        if (SawInit::init($config)) {
            out('configured. input...');
            if (!(SawInit::connect() or SawInit::start())) {
                out('Saw start failed');
                throw new \Exception('Framework starting fail');
            }
            out('work start');
            SawInit::work();
            out('input end');

            SawInit::stop();
            out('closed');
        }
    } catch (Exception $e) {
        switch (PHP_SAPI) {
            case 'cli':

                break;
            default:
                header('HTTP/1.1 503 Service Unavailable');
                echo sprintf('<p style="color:red">%s</p>', $e->getMessage());
        }
    }

}