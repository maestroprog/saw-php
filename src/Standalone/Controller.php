<?php

namespace Saw\Standalone;

use Esockets\Server;
use Saw\Heading\controller\ControllerCore;
use Saw\Command\ThreadKnow;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Command\CommandHandler as EntityCommand;
use Esockets\debug\Log;

/**
 * Связующее звено между входным скриптом,
 * обеспечивающее контроль за работой Worker-ов.
 */
final class Controller
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
    public $dispatchSignals = false;

    /**
     * @var int множитель задач
     */
    public $workerMultiplier = 1;

    /**
     * @var int количество инстансов
     */
    public $workerMax = 1;

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var ControllerCore
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
        $this->dispatcher = SawFactory::getInstance()->createDispatcher([
            new EntityCommand(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (AbstractCommand $context) {
                    return $this->core->wAdd($context->getPeer()->getDsc());
                }
            ),
            new EntityCommand(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (AbstractCommand $context) {
                    $this->core->wDel($context->getPeer()->getDsc());
                }
            ),
            new EntityCommand(
                ThreadKnow::NAME,
                ThreadKnow::class,
                function (AbstractCommand $context) {
                    $this->core->tAdd($context->getPeer()->getDsc(), $context->getData()['name']);
                }
            ),
            new EntityCommand(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    $this->core->tRun($context->getRunId(), $context->getPeer()->getDsc(), $context->getName());
                }
            ),
            new EntityCommand(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    $this->core->tRes(
                        $context->getRunId(),
                        $context->getPeer()->getDsc(),
                        $context->getFromDsc(),
                        $context->getResult()
                    );
                }
            ),
        ]);
        $this->core = new ControllerCore(
            $this->ss,
            $this->dispatcher,
            $this->php_binary_path,
            $this->worker_path,
            $this->workerMultiplier,
            $this->workerMax
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
            $this->dispatchSignals = true;
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
            if ($this->dispatchSignals) {
                pcntl_signal_dispatch();
            }
            if ($this->ss->select()) {
                $this->ss->listen(); // слушаем кто присоединился
                $this->ss->read(); // читаем входящие запросы
            }
            echo 'it', PHP_EOL;

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
