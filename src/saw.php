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

    require_once 'common/Net.php';
    require_once 'common/Init.php';

    class SawInit
    {
        use Init;

        public static function start()
        {
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
    }
}

namespace {

    use Saw\SawInit;

    #require_once 'config.php';
    require_once 'controller/config.php';
    SawInit::configure($config);
    out('configured. init...');

    SawInit::socket_client() or SawInit::start() or (out('Saw start failed') or exit);
    out('init end');

    SawInit::socket_close();
    out('closed');

}