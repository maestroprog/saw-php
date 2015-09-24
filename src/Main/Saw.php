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
        for ($i = 0; $i < 10; $i++) {
            self::socket_accept();
            usleep(90000);
        }
        return true;
    }
}