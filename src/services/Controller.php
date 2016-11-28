<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 20.09.2015
 * Time: 21:44
 */

namespace maestroprog\saw\services;

use maestroprog\saw\entity\Task;
use maestroprog\saw\entity\Worker;
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
        $this->ss->onConnectPeer(function (Peer $peer) {
            Log::log('peer connected ' . $peer->getAddress());
            $peer->set(self::KSTATE, self::PEER_NEW);
            $peer->onRead(function ($data) use ($peer) {
                if ($data === 'HELLO') {
                    $peer->set(self::KSTATE, self::PEER_ACCEPTED);
                    $peer->send('ACCEPT');
                } elseif ($peer->get(self::KSTATE) !== self::PEER_ACCEPTED) {
                    $peer->send('HELLO');
                } elseif (!is_array($data) || !isset($data['command'])) {
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
        });
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
    }

    const KSTATE = 'state';

    /**
     * Наши воркеры.
     *
     * @var Worker[]
     */
    private $workers = [];

    /**
     * Известные воркерам задачи.
     *
     * @var bool[][] $name => $dsc => true
     */
    private $tasksKnow = [];

    /**
     * Очередь новых поступающих задач.
     *
     * @var Task[]
     */
    private $taskNew = [];

    /**
     * Ассоциация названия задачи с его ID
     *
     * @var int[string] $name => $tid
     */
    private $taskAssoc = [];

    /**
     * Выполняемые воркерами задачи.
     *
     * @var Task[]
     */
    private $taskRun = [];

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
        $this->workers[$dsc] = new Worker($dsc, $address);
        return true;
    }

    private function wDel(int $dsc)
    {
        $this->server->getPeerByDsc($dsc)->send(['command' => 'wdel']);
        unset($this->workers[$dsc]);
        // TODO
        // нужно запилить механизм перехвата невыполненных задач
        foreach ($this->tasks as $name => $workers) {
            if (isset($workers[$dsc])) {
                unset($this->tasks[$name][$dsc]);
            }
        }
    }

    /**
     * Функция добавляет задачу в список известных воркеру задач.
     *
     * @param int $dsc
     * @param string $name
     */
    private function tAdd(int $dsc, string $name)
    {
        static $tid = 0; // task ID
        if (!isset($this->taskAssoc[$name])) {
            $this->taskAssoc[$name] = $tid++;
        }
        if (!$this->workers[$dsc]->isKnowTask($tid)) {
            $this->workers[$dsc]->addKnowTask($this->taskAssoc[$name]);
            $this->tasksKnow[$name][$dsc] = true;
        }
    }

    /**
     * Функция добавляет задачу в очередь на выполнение для заданного воркера.
     *
     * @param int $dsc
     * @param string $name
     */
    private function tRun(int $dsc, string $name)
    {
        static $rid = 0; // task run ID
        $this->taskNew[$rid] = new Task($this->taskAssoc[$name], $rid, $name, $dsc);
        $rid++;
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
        foreach ($this->taskNew as $rid => $task) {
            $worker = $this->wMinT($task->getName(), function (Worker $worker) {
                return $worker->getState() != Worker::STOP;
            });
            if ($worker >= 0) {
                $workerPeer = $this->server->getPeerByDsc($worker);
                if ($workerPeer->send(['command' => 'trun', 'name' => $task->getName()])) {
                    $this->workers[$worker]->addTask($task);
                    $this->taskRun[$rid] = $task;
                }
            }
        }
    }

    /**
     * Функция выбирает наименее загруженный воркер.
     *
     * @param string $name задача
     * @param callable|null $filter ($data)
     * @return int
     */
    private function wMinT($name, callable $filter = null) : int
    {
        $selectedDsc = -1;
        foreach ($this->workers as $dsc => $worker) {
            if (!is_null($filter) && !$filter($worker)) {
                // отфильтровали
                continue;
            }
            if (!$worker->isKnowTask($this->taskAssoc[$name])) {
                // не знает такую задачу
                continue;
            }
            if (!isset($count)) {
                $count = $worker->getCountTasks();
                $selectedDsc = $dsc;
            } elseif ($count > ($newCount = $worker->getCountTasks())) {
                $count = $newCount;
                $selectedDsc = $dsc;
            }
        }
        return $selectedDsc;
    }
}
