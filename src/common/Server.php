<?php
/**
 * Server Net code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 01.10.2015
 * Time: 19:48
 */

namespace Saw\Net;


class Server extends Net
{
    /* server variables */

    /**
     * @var array
     */
    protected $connections = [];

    /* event variables */

    /**
     * @var callable
     */
    private $event_disconnect;

    /**
     * @var callable
     */
    private $event_accept;


    /* other variables */

    /**
     * for double use
     * @var bool
     */
    private $_open_try = false;

    public function open()
    {
        return $this->_open();
    }

    /**
     * close server
     */
    public function close()
    {
        parent::close();
        // /@TODO recheck this code
        // socket_close($this->_socket);
        error_log('is unix domain: ' . ($this->socket_domain == AF_UNIX ? 'true' : 'false'));
        if ($this->socket_domain === AF_UNIX) {
            if (file_exists($this->socket_address))
                unlink($this->socket_address);
            else
                trigger_error(sprintf('Pipe file "%s" not found', $this->socket_address));
        }
    }

    public function doDisconnect($client)
    {
        /**
         * TODO
         */
    }

    public function onDisconnect(callable $callback)
    {
        /**
         * TODO
         */
    }

    public function doAccept()
    {
        if ($connection = socket_accept($this->connection)) {
            out('accepted whois?');
            $this->_onAccept($connection);
            //$this->connections[] = $connection;
            //return true;
        }
        //return false;
    }

    public function onAccept(callable $callback)
    {
        $this->event_accept = $callback;
    }

    protected function _onAccept(&$connection)
    {
        $addr= $addr2=null;
        $pname = socket_getpeername($connection, $addr);
        $sname = socket_getsockname($connection, $addr2);

        out(sprintf('peer name: %s, socket name: %, %s, %s', $pname, $sname, $addr, $addr2));
    }

    private function _open()
    {
        if ($this->connection = socket_create($this->socket_domain, SOCK_STREAM, $this->socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_bind($this->connection, $this->socket_address, $this->socket_port)) {
                if (socket_listen($this->connection)) {
                    socket_set_nonblock($this->connection);
                    return true;
                } else {
                    throw new \Exception(socket_strerror(socket_last_error($this->connection)));
                }
            } else {
                $error = socket_last_error($this->connection);
                socket_clear_error($this->connection);
                error_log('error: ' . $error);
                switch ($error) {
                    case SOCKET_EADDRINUSE:
                        // если сокет уже открыт - пробуем его закрыть и снова открыть
                        // @TODO socket close self::socket_close();
                        // @todo recheck this code
                        // closing socket and try restart
                        $this->close();
                        if (!$this->_open_try) {
                            $this->_open_try = true;
                            return $this->_open();
                        }
                        break;
                    default:
                        throw new \Exception(socket_strerror($error));
                }
            }
        }
        // @TODO delete next line...
        trigger_error('Server open failed', E_USER_ERROR);
        return false;
    }
}