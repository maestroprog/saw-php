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


trait Net
{
    protected static $socket_domain = AF_UNIX;

    protected static $socket_address = '127.0.0.1';

    protected static $port = 0;

    private static $_socket;

    public static function socket_server()
    {
        if (self::$_socket = socket_create(self::$socket_domain, SOCK_STREAM, self::$socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_bind(self::$_socket, self::$socket_address, self::$port)) {

            } else {
                $error = socket_last_error(self::$_socket);
            }
        }
    }

    public static function socket_client()
    {
        if (self::$_socket = socket_create(self::$socket_domain, SOCK_STREAM, self::$socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            if (socket_connect(self::$_socket, self::$socket_address, self::$port)) {
                return true;
            } elseif (socket_last_error(self::$_socket) === SOCKET_ECONNREFUSED) {
                return false;
            } else {
                throw new \Exception(socket_strerror(socket_last_error(self::$_socket)));
            }
        }
        trigger_error('SOCKET CREATE FAIL!', E_ERROR);
        return false;
    }

    public static function socket_close()
    {
        if (self::$_socket) {
            socket_close(self::$_socket);
        }
    }
}