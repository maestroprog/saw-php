<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 09.12.16
 * Time: 20:22
 */

namespace maestroprog\saw\library\controller;

use maestroprog\esockets\debug\Log;
use maestroprog\esockets\TcpServer;
use maestroprog\saw\command\TaskRes;
use maestroprog\saw\command\TaskRun;
use maestroprog\saw\entity\Task;
use maestroprog\saw\entity\controller\Worker;
use maestroprog\saw\library\CommandDispatcher;
use maestroprog\saw\library\Executor;

final class Core
{
    use Executor;
    /**
     * @var TcpServer
     */
    private $server;

    private $dispatcher;

    private $workerPath;

    /**
     * @var int множитель задач
     */
    private $workerMultiplier;

    /**
     * @var int количество инстансов
     */
    private $workerMax;

    public function __construct(
        TcpServer $server,
        CommandDispatcher $dispatcher,
        string $phpBinaryPath,
        string $workerPath,
        int $workerMultiplier,
        int $workerMax
    )
    {
        $this->server = $server;
        $this->dispatcher = $dispatcher;
        $this->php_binary_path = $phpBinaryPath;
        $this->workerPath = $workerPath;
        $this->workerMultiplier = $workerMultiplier;
        $this->workerMax = $workerMax;
    }

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

    public function wAdd(int $dsc): bool
    {
        if (!$this->running) {
            return false;
        }
        $this->running = 0;
        $this->workers[$dsc] = new Worker();
        return true;
    }

    public function wDel(int $dsc)
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
    public function tAdd(int $dsc, string $name)
    {
        static $tid = 0; // task ID
        if (!isset($this->taskAssoc[$name])) {
            $this->taskAssoc[$name] = $tid;
        }
        if (!$this->workers[$dsc]->isKnowTask($tid)) {
            $this->workers[$dsc]->addKnowTask($this->taskAssoc[$name]);
            $this->tasksKnow[$name][$dsc] = true;
        }
        $tid++;
    }

    /**
     * Функция добавляет задачу в очередь на выполнение для заданного воркера.
     *
     * @param int $dsc
     * @param string $name
     */
    public function tRun(int $dsc, string $name)
    {
        static $rid = 0; // task run ID
        $this->taskNew[$rid] = new Task($rid, $name, $dsc);
        $rid++;
    }

    public function tRes(int $rid, int $dsc, &$result)
    {
        $peer = $this->server->getPeerByDsc($dsc);
        // @todo empty name!
        $task = new Task($rid, '', $dsc, Task::END);
        $task->setResult($result);
        $this->dispatcher->create(TaskRes::NAME, $peer)
            ->onError(function () {
                //todo
            })
            ->onSuccess(function () {
                //todo
            })
            ->run(TaskRes::serializeTask($task));
    }

    public function wBalance()
    {
        if (count($this->workers) < $this->workerMax && !$this->running) {
            // run new worker
            $this->exec($this->workerPath);
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
    public function tBalance()
    {
        foreach ($this->taskNew as $rid => $task) {
            if (!isset($this->tasksKnow[$task->getName()])) {
                continue;
            }
            $worker = $this->wMinT($task->getName(), function (Worker $worker) {
                return $worker->getState() !== Worker::STOP;
            });
            if ($worker >= 0) {
                $workerPeer = $this->server->getPeerByDsc($worker);
                try {
                    /** @var $command TaskRun */
                    $this->dispatcher->create(TaskRun::NAME, $workerPeer)
                        ->onError(function () use ($task) {
                            //todo
                        })
                        ->onSuccess(function () use ($worker, $rid, $task) {
                            $this->workers[$worker]->addTask($task);
                            $this->taskRun[$rid] = $task;
                            unset($this->taskNew[$rid]);
                        })
                        ->run(TaskRun::serializeTask($task));
                } catch (\Throwable $e) {
                    throw new \Exception('Cannot balanced Task ' . $task->getRunId(), 0, $e);
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
    private function wMinT($name, callable $filter = null): int
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
