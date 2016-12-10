<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:44
 */

namespace maestroprog\saw\service;

use maestroprog\library\controller\Core;
use maestroprog\saw\command\WorkerAdd;
use maestroprog\saw\command\WorkerDelete;
use maestroprog\saw\library\Command;
use maestroprog\saw\library\Dispatcher;
use maestroprog\saw\library\Factory;
use maestroprog\saw\library\Singleton;
use maestroprog\esockets\Peer;
use maestroprog\esockets\TcpServer;
use maestroprog\esockets\debug\Log;

/**
 * Связующее звено между входным скриптом,
 * обеспечивающее контроль за работой Worker-ов.
 */
class Controller extends Singleton
{

    protected static $instance;
    /**
     * Константы возможных типов подключающихся клиентов
     */
    const CLIENT_INPUT = 1; // input-клиент, передающий запрос
    const CLIENT_WS_INPUT = 2; // WS input-клиент, передающий запрос (зарезервировано)
    const CLIENT_WORKER = 3; // воркер
    const CLIENT_CONTROLLER = 4; // контроллер. (зарезервировано)
    const CLIENT_DEBUG = 5; // отладчик
    /**
     * Константы возможных состояний подключения с клиентом
     */
    const PEER_NEW = 0; // новое соединение
    const PEER_ACCEPTED = 1; // соединение принято

    /**
     * @var bool
     */
    public $work = true;

    /**
     * @var bool вызывать pcntl_dispatch_signals()
     */
    public $dispatch_signals = false;

    public $worker_path = 'worker.php';

    /**
     * @var int множитель задач
     */
    public $worker_multiplier = 1;

    /**
     * @var int количество инстансов
     */
    public $worker_max = 1;

    /**
     * @var TcpServer
     */
    protected $server;

    /**
     * @var TcpServer socket connection
     */
    private $ss;

    private $core;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * Инициализация
     *
     * @param array $config
     * @return bool
     */
    public function init(array &$config)
    {
        // настройка сети
        if (isset($config['net'])) {
            $this->ss = new TcpServer($config['net']);
        } else {
            trigger_error('Net configuration not found', E_USER_NOTICE);
            unset($config);
            return false;
        }
        $this->configure($config);
        $this->core = new Core($this->ss, $this->worker_path, $this->worker_multiplier, $this->worker_max);
        $this->dispatcher = Factory::getInstance()->createDispatcher([
            WorkerAdd::NAME => WorkerAdd::class,
            WorkerDelete::NAME => WorkerDelete::class,
        ]);
        return true;
    }

    private function configure(&$config)
    {
        // настройка доп. параметров
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => &$param) {
                if (isset($this->$key)) $this->$key = $param;
                unset($param);
            }
        }
    }

    /**
     * Старт контроллера.
     *
     * @return bool
     * @throws \Exception
     */
    public function start()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function ($sig) {
                $this->work = false;
            });
            $this->dispatch_signals = true;
        }

        if (!$this->ss->connect()) {
            throw new \Exception('Cannot start: not connected');
        }
        Log::log('start');
        $this->ss->onConnectPeer($this->onConnectPeer());
        register_shutdown_function(function () {
            $this->stop();
            Log::log('stopped');
        });
        return true;
    }

    /**
     * Заставляем работать контроллер :)
     */
    public function work()
    {
        while ($this->work) {
            $this->ss->listen(); // слушаем кто присоединился
            $this->ss->read(); // читаем входящие запросы
            $this->wBalance(); // балансируем воркеры
            $this->tBalance(); // раскидываем задачки
            if ($this->dispatch_signals) {
                pcntl_signal_dispatch();
            }
            usleep(INTERVAL);
        }
    }

    public function stop()
    {
        $this->work = false;
        $this->ss->disconnect();
        Log::log('closed');
    }

    protected function onConnectPeer()
    {
        return function (Peer $peer) {
            Log::log('peer connected ' . $peer->getAddress());
            $peer->set(self::KSTATE, self::PEER_NEW);
            $peer->onRead(function ($data) use ($peer) {
                if ($data === 'HELLO') {
                    $peer->set(self::KSTATE, self::PEER_ACCEPTED);
                    $peer->send('ACCEPT');
                } elseif ($peer->get(self::KSTATE) !== self::PEER_ACCEPTED) {
                    $peer->send('HELLO');
                } elseif (!is_array($data) || !$this->dispatcher->valid($data)) {
                    $peer->send('INVALID');
                } else {
                    $this->handle($data, $peer);
                }
            });
            $peer->onDisconnect(function () use ($peer) {
                Log::log('peer disconnected');
            });
            if (!$peer->send('HELLO')) {
                Log::log('HELLO FAIL SEND!');
                $peer->disconnect(); // не нужен нам такой клиент
            }
        };
    }

    protected function handle(array $data, Peer $peer)
    {
        try {
            $command = $this->dispatcher->dispatch($data, $peer);
            $command->handle($data['data']);
            if ($command->getState() === Command::STATE_RUN) {
                switch ($command->getCommand()) {
                    case WorkerAdd::NAME: // add worker
                        if ($this->wAdd($peer)) {
                            $command->success();
                        } else {
                            $command->error();
                        }
                        break;
                    case 'wdel': // del worker
                        $this->wDel($peer->getDsc());
                        break;
                    case 'tadd': // add new task (сообщает что воркеру стала известна новая задача)
                        $this->tAdd($peer->getDsc(), $data['name']);
                        break;
                    case 'trun': // run task (name) (передает на запуск задачи в очередь)
                        $this->tRun($peer->getDsc(), $data['name']);
                        break;
                    default:
                        throw new \Exception('Undefined command ' . $command->getCommand());
                }
            } elseif ($command->getState() === Command::STATE_RES) {

            }
        } catch (\Throwable $e) {
            // todo
            return;
        }
    }

    const KSTATE = 'state';

    /**
     * @param array $config
     * @return Controller
     * @throws \Exception
     */
    public static function create(array $config): Controller
    {
        $controller = self::getInstance();
        if ($controller->init($config)) {
            Log::log('configured. start...');
            if (!$controller->start()) {
                Log::log('Saw start failed');
                throw new \Exception('Saw start failed');
            }
            Log::log('start end');
        }
        return $controller;
    }
}
