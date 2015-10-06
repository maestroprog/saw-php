<?php
/**
 ** Saw entry gate file
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 19:01
 */

namespace Saw {

    $common_dir = realpath(__DIR__ . '/../common') . '/';

    require_once $common_dir . 'Net.php';
    require_once $common_dir . 'Client.php';

    unset($common_dir);

    class SawInit
    {
        /**
         * @var Net\Client socket connection
         */
        private static $sc;

        public static function init(&$config)
        {
            foreach ($config as $category => &$values) {
                switch ($category) {
                    case '';
                }
            }
            $before_run = microtime(true);
            exec(self::$php_binary_path . ' -f ' . __DIR__ . '/controller/start.php > /dev/null &');
            $after_run = microtime(true);
            #usleep(100000); // await for run controller Saw
            $try = 0;
            do {
                $try_run = microtime(true);
                #usleep(100000);
                usleep(100);
                if (@self::socket_client()) {
                    printf('run: %f, exec: %f, connected: %f', $before_run, $after_run - $before_run, $try_run - $after_run);
                    error_log($before_run);
                    return true;
                }
            } while ($try++ < 1000000000);
            printf('false');
            return false;
        }

        public static function pre_init()
        {
            self::$sc = new Net\Client();
            // TODO: Implement pre_init() method.
        }

        public static function post_init()
        {
            // TODO: Implement post_init() method.
        }

    }
}

namespace {

    use Saw\SawInit;

    $config = require 'config.php';
    #require_once 'controller/config.php';
    if (SawInit::init($config)) {

        out('configured. input...');
        SawInit::socket_client() or SawInit::start() or (out('Saw start failed') or exit);
        out('input end');

        SawInit::socket_close();
        out('closed');
    }

}