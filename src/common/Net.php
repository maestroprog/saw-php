<?php
/**
 ** Net code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:42
 */

namespace Saw\Net;


class Net
{
    protected $socket_domain = AF_UNIX;

    protected $socket_address = '127.0.0.1';

    protected $port = 0;

    protected $connection;

    public function __construct($config)
    {
        foreach ($config as $key => $val) {
            if (isset($this->{$key})) $this->{$key} = $val;
        }
    }

    public static function socket_send()
    {

    }
}