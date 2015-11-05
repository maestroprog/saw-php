<?php
/**
 * Net Client code snippet
 *
 * Created by PhpStorm.
 * User: yarullin
 * Date: 02.10.2015
 * Time: 8:55
 */

namespace Saw\Net;

/**
 * Class Client
 * @package Saw\Net
 */
class Client extends Net
{
    /**
     * @var bool connection state
     */
    private $connected = false;

    /* event variables */

    /**
     * @var callable
     */
    private $event_disconnect;

    public function connect()
    {
        return $this->connected ?: $this->_connect();
    }

    public function close()
    {
        parent::close();
        $this->connected = false;
    }

    public function doDisconnect()
    {
        $this->close();
    }

    public function onDisconnect(callable $callback)
    {
        $this->event_disconnect = $callback;
    }

    protected function _onDisconnect()
    {
        if (is_callable($this->event_disconnect)) {
            call_user_func($this->event_disconnect);
        }
    }

    private function _connect()
    {
        if ($this->connection = socket_create($this->socket_domain, SOCK_STREAM, $this->socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_connect($this->connection, $this->socket_address, $this->socket_port)) {
                $this->setNonBlock();// $this->connection); // устанавливаем неблокирующий режим работы сокета
                return $this->connected = true;
            } else {
                $error = socket_last_error($this->connection);
                socket_clear_error($this->connection);
                switch ($error) {
                    case SOCKET_ECONNREFUSED:
                    case SOCKET_ENOENT:
                        // если отсутствует файл сокета, либо соединиться со слушающим сокетом не удалось - возвращаем false
                        $this->close();
                        return false;
                    default:
                        // в иных случаях кидаем необрабатываемое исключение
                        throw new \Exception(socket_strerror($error));

                }
            }
        }
        // @TODO delete next line...
        trigger_error('Client connect failed', E_USER_ERROR);
        return false;
    }

}