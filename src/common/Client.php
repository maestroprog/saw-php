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

    public static function socket_client()
    {
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
            if (self::$socket_domain === AF_UNIX) {
                unlink(self::$socket_address);
            }
        }
    }
}