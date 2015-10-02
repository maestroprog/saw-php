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
    use Net, Init;

    public static function start()
    {
        out('start');
        error_log(sprintf('start accepting am %f', microtime(true)));
        for ($i = 0; $i < 1000000; $i++) {
            if (self::socket_accept()){
                sprintf('accepted am %f and try %d', microtime(true), $i);
                return true;
            }
            usleep(100);
        }
        return true;
    }
}