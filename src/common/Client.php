<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 02.10.2015
 * Time: 8:55
 */

namespace Saw\Net;


class Client extends Net
{

    public function socket_client()
    {
        if ($this->_socket = socket_create($this->socket_domain, SOCK_STREAM, $this->socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_connect($this->_socket, $this->socket_address, $this->port)) {
                return true;
            } else {
                $error = socket_last_error($this->_socket);
                socket_clear_error($this->_socket);
                switch ($error) {
                    case SOCKET_ECONNREFUSED:
                    case SOCKET_ENOENT:
                        // если отсутствует файл сокета, либо соединиться со слушающим сокетом не удалось - возвращаем false
                        self::socket_close();
                        return false;
                    default:
                        // в иных случаях кидаем необрабатываемое исключение
                        throw new \Exception(socket_strerror($error));

                }
            }
        }
        trigger_error('CLIENT SOCKET CREATE FAILED!', E_USER_ERROR);
        return false;
    }

    protected function socket_close()
    {
        if ($this->_socket) {
            socket_close($this->_socket);
            error_log('is unix domain: ' . ($this->socket_domain == AF_UNIX ? 'true' : 'false'));
            if ($this->socket_domain === AF_UNIX) {
                unlink($this->socket_address);
            }
        }
    }
}