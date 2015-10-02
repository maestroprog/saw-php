<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 01.10.2015
 * Time: 19:48
 */

namespace Saw\Net;


class Server extends Net
{
    private $_socket;

    protected static $_connections = array();

    public function socket_server($recursive = false)
    {
        if ($this->_socket = socket_create($this->socket_domain, SOCK_STREAM, $this->socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_bind($this->_socket, $this->socket_address, $this->port)) {
                if (socket_listen($this->_socket)) {
                    socket_set_nonblock($this->_socket);
                    return true;
                } else {
                    throw new \Exception(socket_strerror(socket_last_error($this->_socket)));
                }
            } else {
                $error = socket_last_error($this->_socket);
                socket_clear_error($this->_socket);
                error_log('error: ' . $error);
                switch ($error) {
                    case SOCKET_EADDRINUSE:
                        self::socket_close(); // closing socket and try restart
                        if (!$recursive)
                            return self::socket_server(true);
                        break;
                    default:
                        throw new \Exception(socket_strerror($error));
                }
            }
        }
        trigger_error('LISTEN SOCKET CREATE FAILED!', E_USER_ERROR);
        return false;
    }

    protected function socket_accept()
    {
        if (socket_accept($this->_socket)) {
            out('accepted whois?');
            return true;
        }
        return false;
    }
}