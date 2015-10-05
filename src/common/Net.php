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


abstract class Net
{
    /**
     * @var int type of socket
     */
    protected $socket_domain = AF_UNIX;

    /**
     * @var string address of socket connection
     */
    protected $socket_address = '127.0.0.1';

    /**
     * @var int port of socket connection
     */
    protected $socket_port = 0;

    /**
     * @var resource of socket connection
     */
    protected $connection;

    public function __construct($config)
    {
        foreach ($config as $key => $val)
            if (isset($this->{$key})) $this->{$key} = $val;
    }

    abstract public function doReceive();

    abstract public function onReceive(callable $callback);

    abstract public function send($data);

    public function close()
    {
        if ($this->connection)
            socket_close($this->connection);
        else
            trigger_error('Server socket already closed');
    }

}