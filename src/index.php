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

    class SawInit
    {
        use Net, Init;

        public static function start()
        {
            exec(self::$php_binary_path . ' -f ' . __DIR__ . '/start.php > /dev/null &');
            $try = 0;
            do {
                usleep(100000);
                if (self::socket_client()) return true;
            } while ($try++ < 10);
            return false;
        }
    }
}

namespace {

    use Saw\SawInit;

    require_once 'config.php';
    SawInit::configure($config);
    out('configured. init...');

    SawInit::socket_client() or SawInit::start() or (out('Saw start failed') or exit);
    out('init end');

    SawInit::socket_close();
    out('closed');
}