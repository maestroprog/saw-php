<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:44
 */

namespace maestroprog\saw\service;

use maestroprog\saw\library\controller\Core;
use maestroprog\saw\command\TaskAdd;
use maestroprog\saw\command\TaskRes;
use maestroprog\saw\command\TaskRun;
use maestroprog\saw\command\WorkerAdd;
use maestroprog\saw\command\WorkerDelete;
use maestroprog\saw\entity\Command as EntityCommand;
use maestroprog\saw\library\dispatcher\Command;
use maestroprog\saw\library\CommandDispatcher;
use maestroprog\saw\library\Factory;
use maestroprog\saw\library\Singleton;
use maestroprog\esockets\Peer;
use maestroprog\esockets\TcpServer;
use maestroprog\esockets\debug\Log;

/**
 * Связующее звено между входным скриптом,
 * обеспечивающее контроль за работой Worker-ов.
 */
final class Controller extends Singleton
{
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

    public $php_binary_path = 'php';

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

    /**
     * @var Core
     */
    private $core;

    /**
     * @var CommandDispatcher
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
        $this->dispatcher = Factory::getInstance()->createDispatcher([
            new EntityCommand(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (Command $context) {
                    return $this->core->wAdd($context->getPeer()->getDsc());
                }
            ),
            new EntityCommand(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (Command $context) {
                    $this->core->wDel($context->getPeer()->getDsc());
                }
            ),
            new EntityCommand(
                TaskAdd::NAME,
                TaskAdd::class,
                function (Command $context) {
                    $this->core->tAdd($context->getPeer()->getDsc(), $context->getData()['name']);
                }
            ),
            new EntityCommand(
                TaskRun::NAME,
                TaskRun::class,
                function (TaskRun $context) {
                    $this->core->tRun($context->getRunId(), $context->getPeer()->getDsc(), $context->getName());
                }
            ),
            new EntityCommand(
                TaskRes::NAME,
                TaskRes::class,
                function (TaskRes $context) {
                    $this->core->tRes(
                        $context->getRunId(),
                        $context->getPeer()->getDsc(),
                        $context->getFromDsc(),
                        $context->getResult()
                    );
                }
            ),
        ]);
        $this->core = new Core(
            $this->ss,
            $this->dispatcher,
            $this->php_binary_path,
            $this->worker_path,
            $this->worker_multiplier,
            $this->worker_max
        );
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
                $this->ss->disconnect();
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
            $this->core->wBalance(); // балансируем воркеры
            $this->core->tBalance(); // раскидываем задачки
            if ($this->dispatch_signals) {
                pcntl_signal_dispatch();
            }
            if ($this->ss->select()) {
                $this->ss->listen(); // слушаем кто присоединился
                $this->ss->read(); // читаем входящие запросы
            }
            echo 'it' , PHP_EOL;

            usleep(INTERVAL * 10);
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
            //$peer->setBlock();
            Log::log('peer connected ' . $peer->getAddress());
            $peer->set(self::KSTATE, self::PEER_NEW);
            $peer->onRead(function ($data) use ($peer) {
                Log::log('I RECEIVED  :) from ' . $peer->getDsc() . $peer->getAddress());
                Log::log(var_export($data, true));
                if ($data === 'HELLO' && $peer->get(self::KSTATE) !== self::PEER_ACCEPTED) {
                    $peer->set(self::KSTATE, self::PEER_ACCEPTED);
                    $peer->send('ACCEPT');
                } elseif ($peer->get(self::KSTATE) !== self::PEER_ACCEPTED) {
                    $peer->send('HELLO');
                } elseif (!is_array($data) || !$this->dispatcher->valid($data)) {
                    $peer->send('INVALID');
                } else {
                    $this->dispatcher->dispatch($data, $peer);
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
