<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:44
 */

namespace maestroprog\saw\services;

use maestroprog\saw\library\Singleton;
use maestroprog\saw\library\Executor;
use maestroprog\esockets\Peer;
use maestroprog\esockets\TcpServer;
use maestroprog\esockets\debug\Log;

/**
 * Связующее звено между входным скриптом,
 * обеспечивающее контроль за работой Worker-ов.
 */
class Controller extends Singleton
{
    use Executor;

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
    const STATE_ACCEPTED = 1; // соединение принято

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
        // настройка доп. параметров
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => &$param) {
                if (isset($this->$key)) $this->$key = $param;
                unset($param);
            }
        }
        unset($config);
        return true;
    }

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
        $this->ss->onConnectPeer(function (Peer $peer) {
            Log::log('peer connected ' . $peer->getAddress());
            $peer->set('state', self::STATE_ACCEPTED);
            $peer->onRead(function ($data) use ($peer) {
                if (!is_array($data) || !isset($data['command'])) {
                    return $peer->send('BYE');
                }
                return $this->handle($data, $peer);
            });
            $peer->onDisconnect(function () use ($peer) {
                Log::log('peer disconnected');
            });
            if (!$peer->send('HELLO')) {
                Log::log('HELLO FAIL SEND!');
            }
        });
        register_shutdown_function(function () {
            $this->stop();
            Log::log('stopped');
        });
        return true;
    }

    protected function handle(array $data, Peer $peer)
    {
        switch ($data['command']) {
            case 'wadd': // add worker
                if ($this->wAdd($peer->getDsc(), $peer->getAddress())) {
                    $peer->send(['command' => 'wadd']);
                } else {
                    $peer->send(['command' => 'wdel']);
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
        }
        return null;
    }

    const WNEW = 0;
    const WREADY = 1;
    const WRUN = 2;
    const WSTOP = 3;

    const KDSC = 'dsc';
    const KADDR = 'address';
    const KSTATE = 'state';
    const KTASKS = 'tasks';

    const TNEW = 0;
    const TRUN = 1;
    const TERR = 2;

    const KTID = 'tid';
    const KNAME = 'name';

    /**
     * Известные воркеры.
     *
     * @var array
     */
    private $workers = [];

    /**
     * Известные известным воркерам задачи.
     *
     * @var array
     */
    private $tasks = [];

    /**
     * Выполняемые воркерами задачи.
     *
     * @var array
     */
    private $trun = [];

    /**
     * @var array[][] $name => $tid => []
     */
    private $tnew = [];

    /**
     * @var int Состояние запуска нового воркера.
     */
    private $running = 0;

    private function wAdd(int $dsc, string $address) : bool
    {
        if (!$this->running) {
            return false;
        }
        $this->running = 0;
        $this->workers[$dsc] = [
            self::KDSC => $dsc,
            self::KADDR => $address,
            self::KSTATE => self::WREADY,
            self::KTASKS => []
        ];
        return true;
    }

    private function wDel(int $dsc)
    {
        $this->server->getPeerByDsc($dsc)->send(['command' => 'wdel']);
        unset($this->workers[$dsc]);
        foreach ($this->tasks as $name => $workers) {
            if (isset($workers[$dsc])) {
                unset($this->tasks[$name][$dsc]);
            }
        }
    }

    private function tAdd(int $dsc, string $name)
    {
        if (!isset($this->workers[$dsc][self::KTASKS][$name])) {
            $this->workers[$dsc][self::KTASKS][$name] = true;
            $this->tasks[$name][$dsc] = true;
        }
    }

    /**
     * Функция добавляет задачу в очередь на выполнение.
     *
     * @param int $dsc
     * @param string $name
     */
    private function tRun(int $dsc, string $name)
    {
        static $tid = 0;
        $this->tnew[$tid] = [
            self::KTID => $tid,
            self::KNAME => $name,
            self::KDSC => $dsc,
            self::KSTATE => self::TNEW
        ];
        $this->running = 0;
    }

    private function wBalance()
    {
        if (count($this->workers) < $this->worker_max && !$this->running) {
            // run new worker
            $this->exec($this->worker_path);
            $this->running = time();
        } elseif ($this->running && $this->running < time() - 10) {
            // timeout 10 sec
            $this->running = 0;
            Log::log('Can not run worker!');
        } else {
            // so good; всё ок
        }
    }

    /**
     * Функция разгребает очередь задач, раскидывая их по воркерам.
     */
    private function tBalance()
    {
        foreach ($this->tnew as $tid => $data) {
            $worker = $this->wMinT(function ($data) {
                return $data[self::KSTATE] != self::WSTOP;
            });
            if ($worker >= 0) {
                $this->workers[$worker][self::KTASKS][$tid] = $tid;
                //$this->trun

            }
        }
    }

    private function wMinT(callable $filter = null) : int
    {
        $selectedDsc = -1;
        foreach ($this->workers as $dsc => $data) {
            if (!is_null($filter) && !$filter($data)) {
                continue;
            }
            if (!isset($count)) {
                $count = count($data[self::KTASKS]);
                $selectedDsc = $dsc;
            } elseif ($count > ($newCount = count($data[self::KTASKS]))) {
                $count = $newCount;
                $selectedDsc = $dsc;
            }
        }
        return $selectedDsc;
    }

    public function work()
    {
        while ($this->work) {
            $this->ss->listen();
            $this->ss->read();
            $this->wBalance();
            $this->tBalance();
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
}
