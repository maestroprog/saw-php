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
    const DATA_RAW = 0;
    const DATA_JSON = 1;
    const DATA_CHUNK = 2;
    const DATA_CHUNK_CONTROL = 4;
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

    /**
     * @var array user-defined variables and flags of the connection
     */
    protected $vars = [];


    /* event variables */

    /**
     * @var callable
     */
    private $event_receive;

    public function __construct($config)
    {
        foreach ($config as $key => $val)
            if (isset($this->{$key})) $this->{$key} = $val;
    }

    public function doReceive()
    {
        // read message meta
        if (($data = $this->_read(4)) !== false) {
            list($length, $flag) = unpack('nn', $data);
            out('read length ' . $length);
            out('flag ' . $flag);
            if ($flag & 0x00ff & self::DATA_CHUNK) {
                $chunks = [];
                $control = false;
                $size = 0;
                $try = 0;
                do {
                    if (!($flag & self::DATA_CHUNK_CONTROL)) {
                        $chunks[$flag & 0xff00 >> 8] = $data;
                    } else {
                        $size = (int)$data;
                    }
                    if ($data = $this->_read(4)) {

                    }
                } while (!$control || count($chunks) < $size);
            } else {
                if (($data = $this->_read($length, true)) !== false) {
                } else {
                    out('cannot retrieve data');
                }
            }
        }
        return false;
    }

    public function onReceive(callable $callback)
    {
        $this->event_receive = $callback;
    }

    public function send($data)
    {
        if (is_string($data)) {
            $type = 0;
        } else {
            $data = json_encode($data);
            $type = 1;
        }
        $length = strlen($data);
        if ($length >= 0xffff) {
            trigger_error('Big data size to send! I can split it\'s', E_USER_WARNING);
            $offset = 0;
            $num = 0;
            do {
                $chunk = substr($data, $offset += 0xffff, 0xffff);
                $length -= $len = strlen($chunk);
                $this->_send($chunk, $len, $num++ << 8 | $type | self::DATA_CHUNK);
                if ($num > 255) {
                    // 255 фреймов должно хватит на 16M
                    trigger_error('Big chunk number!', E_USER_ERROR);
                }
            } while ($length > 0);
            $this->_send($num, strlen($num), self::DATA_CHUNK | self::DATA_CHUNK_CONTROL);
        } else {
            $this->_send($data, $type);
        }
    }

    public function close()
    {
        if ($this->connection)
            socket_close($this->connection);
        else
            trigger_error('Socket already closed');
    }

    public function setBlock()
    {
        socket_set_block($this->connection);
    }

    public function setNonBlock()
    {
        socket_set_nonblock($this->connection);
    }

    /**
     * @param $name
     * @param $value
     * set user variable
     */
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     * get user variable
     */
    public function get($name)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : null;
    }

    protected function _onReceive(&$data)
    {
        if (is_callable($this->event_receive)) {
            call_user_func_array($this->event_receive, $data);
        }
    }

    private function _read($length, $required = false)
    {
        out('read try');
        $buffer = '';
        $try = 0;
        while ($length > 0) {
            $data = socket_read($this->connection, $length);
            if ($data === false) {
                throw new \Exception('Socket read error: ' . socket_strerror(socket_last_error(socket_last_error($this->connection))), socket_last_error($this->connection));
            } elseif ($data === '') {
                trigger_error('Socket read 0 bytes', E_USER_WARNING);
                if (!$required || $try++ > 100) return false;
                usleep(1000);
            } else {
                $buffer .= $data;
                $length -= strlen($data);
            }
        }
        out('read: ' . $buffer);
        return $buffer;
    }

    private function _send($data, $length, $flag = 0)
    {
        $data = pack('nna*', $length, $flag, $data);
        $length += 4;
        $written = 0;
        do {
            $wrote = socket_write($this->connection, $data);
            if ($wrote === false) {
                throw new \Exception('Socket write error: ' . socket_strerror(socket_last_error(socket_last_error($this->connection))), socket_last_error($this->connection));
                //return false;
            } elseif ($wrote === 0) {
                trigger_error('Socket written 0 bytes', E_USER_WARNING);
            } else {
                $data = substr($data, $wrote);
                $written += $wrote;
            }
        } while ($written < $length);
        return true;
    }
}