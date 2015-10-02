<?php
/**
 ** Net code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:42
 */

namespace Saw;


class Net
{
    protected $socket_domain = AF_UNIX;

    protected $socket_address = '127.0.0.1';

    protected $port = 0;

    private $_socket;

    private $_socket_type;

    protected static $_connections = array();


    public static function socket_accept()
    {
        if (socket_accept(self::$_socket)) {
            out('accepted whois?');
            return true;
        }
        return false;
    }

    public static function socket_send()
    {
        
    }

    public static function socket_client()
    {
        self::$_socket_type = 2;
        if (self::$_socket = socket_create(self::$socket_domain, SOCK_STREAM, self::$socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_connect(self::$_socket, self::$socket_address, self::$port)) {
                return true;
            } else {
                $error = socket_last_error(self::$_socket);
                socket_clear_error(self::$_socket);
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

    protected static function socket_close()
    {
        if (self::$_socket) {
            socket_close(self::$_socket);
            error_log('is unix domain: ' . (self::$socket_domain == AF_UNIX ? 'true' : 'false'));
            error_log('is socket server: ' . (self::$_socket_type == 1 ? 'true' : 'false'));
            if (self::$socket_domain === AF_UNIX && self::$_socket_type === 1) {
                unlink(self::$socket_address);
            }
        }
    }
}