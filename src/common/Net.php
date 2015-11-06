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
    const DATA_INT = 2;
    const DATA_FLOAT = 4;
    const DATA_STRING = 8;
    const DATA_ARRAY = 16;
    const DATA_EXTENDED = 32; // reserved for objects
    const DATA_EXTENDED_2 = 64; // reserved
    const DATA_CONTROL = 128;

    const SOCKET_WAIT = 1000; // 1 ms ожидание на повторные операции на сокете
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

    /* private variables */

    /**
     * @var int
     * auto increment message id
     * #for send
     */
    private $mid = 0;

    public function __construct($config)
    {
        foreach ($config as $key => $val)
            if (isset($this->{$key})) $this->{$key} = $val;
    }

    public function doReceive()
    {
        // read message meta
        if (($data = $this->_read(5)) !== false) {
            list($length, $flag) = array_values(unpack('Nvalue0/Cvalue1', $data));
            //out('read length ' . $length);
            //out('flag ' . $flag);
            out('read try ' . $length . ' bytes');
            if (($data = $this->_read($length, true)) !== false) {
                out('data retrieved');
            } else {
                out('cannot retrieve data');
            }
            if ($flag & self::DATA_JSON) {
                $data = json_decode($data, $flag & self::DATA_ARRAY ? true : false);
            } elseif ($flag & self::DATA_INT) {
                $data = (int)$data;
            } elseif ($flag & self::DATA_FLOAT) {
                $data = (float)$data;
            } else {

            }
            if ($flag & self::DATA_CONTROL) {
                // control message parser
                // @TODO
            }
            $this->_onReceive($data);
        }
    }

    public function onReceive(callable $callback)
    {
        $this->event_receive = $callback;
    }

    protected function _onReceive(&$data)
    {
        if (is_callable($this->event_receive)) {
            call_user_func_array($this->event_receive, [$data]);
            return true;
        } else {
            return false;
        }
    }

    public function send($data)
    {
        $this->mid++;
        $flag = 0;
        switch (gettype($data)) {
            case 'boolean':
                trigger_error('Boolean data type cannot be transmitted', E_USER_WARNING);
                return false;
                break;
            case 'integer':
                $flag |= self::DATA_INT;
                break;
            case 'double':
                $flag |= self::DATA_FLOAT;
                break;
            case 'array':
                $flag |= self::DATA_ARRAY | self::DATA_JSON;
                break;
            case 'object':
                $flag |= self::DATA_EXTENDED | self::DATA_JSON;
                trigger_error('Values of type Object cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            case 'resource':
                trigger_error('Values of type Resource cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            case 'NULL':
                trigger_error('Null data type cannot be transmitted', E_USER_WARNING);
                return false;
                break;
            case 'unknown type':
                trigger_error('Values of Unknown type cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            default:
                $flag |= self::DATA_STRING;
        }
        if ($flag & self::DATA_JSON)
            $data = json_encode($data);
        $length = strlen($data);
        if ($length >= 0xffffffff) { // 4294967296 bytes
            trigger_error('Big data size to send! I can split it\'s', E_USER_ERROR); // кто-то попытался передать более 4 ГБ за раз, выдаем ошибку
            // СТОП СТОП СТОП! Какой идиот за раз будет передавать 4 ГБ?
            //...
            return false;
        } else {
            return $this->_send($data, $flag);
        }
    }

    public function close()
    {
        if ($this->connection) {
            socket_shutdown($this->connection);
            socket_close($this->connection);
            $this->_onDisconnect();
        } else {
            trigger_error('Socket already closed');
        }
    }

    abstract protected function _onDisconnect();

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

    private function _read($length, $required = false)
    {
        $buffer = '';
        $try = 0;
        while ($length > 0) {
            $data = socket_read($this->connection, $length);
            if ($data === false) {
                switch (socket_last_error($this->connection)) {
                    case SOCKET_EAGAIN:
                        if (!strlen($buffer) && !$required) {
                            return false;
                        } else {
                            out('Socket read error: SOCKET_EAGAIN at READING');
                            usleep(self::SOCKET_WAIT);
                        }
                        break;
                    default:
                        out('SOCKET READ ERROR!!!' . socket_last_error($this->connection));
                        throw new \Exception('Socket read error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
                }
            } elseif ($data === '') {
                /**
                 * В документации PHP написано, что socket_read выдает false, если сокет отсоединен.
                 * Однако, как выяснилось, это не так. Если сокет отсоединен,
                 * то socket_read возвращает пустую строку. Поэтому в данном блоке будем
                 * обрабатывать ситуацию обрыва связи.
                 * TODO запилить, что описал
                 */
                trigger_error('Socket read 0 bytes', E_USER_WARNING);
                out('Пробуем получить код ошибки...');
                throw new \Exception('Socket read error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
                if ($try++ > 100 && $required) {
                    trigger_error('Fail require read data', E_USER_ERROR);
                }
                return false;
            } else {
                $buffer .= $data;
                $length -= strlen($data);
                $try = 0; // обнуляем счетчик попыток чтения
                usleep(1000);
            }
        }
        return $buffer;
    }

    private function _send($data, $flag = 0)
    {
        $length = strlen($data);
        $data = pack('NCa*', $length, $flag, $data);
        $length += 5;
        $written = 0;
        do {
            $wrote = socket_write($this->connection, $data);
            if ($wrote === false) {
                /**
                 * @TODO как и при чтении, необходимо протестировать работу socket_write
                 * Промоделировать ситуацию, когда удаленный сокет отключился, и выяснить, что выдает socker_write
                 * и как правильно определить отключение удаленного сокета в данной функции.
                 */
                switch (socket_last_error($this->connection)) {
                    case SOCKET_EAGAIN:
                        out('Socket write error: SOCKET_EAGAIN at writing');
                        usleep(self::SOCKET_WAIT);
                        break;
                    default:
                        out('SOCKET READ ERROR!!!' . socket_last_error($this->connection));
                        throw new \Exception('Socket read error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
                }

                throw new \Exception('Socket write error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
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