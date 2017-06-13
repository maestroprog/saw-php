<?php

namespace Saw\Standalone\Controller;

/**
 * Распределитель потоков по воркерам.
 */
class ThreadDistributor implements CycleInterface
{

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
     * Предполагается, что этот метод будет запускать работу по перераспределению потоков по воркерам.
     */
    public function work()
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
                    /** @var $command ThreadRun */
                    $this->commandDispatcher->create(ThreadRun::NAME, $workerPeer)
                        ->onError(function () use ($task) {
                            Log::log('error run task ' . $task->getName());
                            //todo
                        })
                        ->onSuccess(function () use ($worker, $rid, $task) {
                            //$this->taskRun[$rid] = $task;
                        })
                        ->run(ThreadRun::serializeTask($task));
                    // т.к. выполнение задачи на стороне воркера произойдет раньше,
                    // чем возврат ответа с успешным запуском
                    // почистим массив, и запомним что поставили воркеру эту задачу
                    $this->workers[$worker]->addTask($task);
                    unset($this->taskNew[$rid]);
                } catch (\Throwable $e) {
                    throw new \Exception('Cannot balanced Task ' . $task->getRunId(), 0, $e);
                }
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
     * @param int $runId
     * @param int $dsc
     * @param string $name
     */
    public function tRun(int $runId, int $dsc, string $name)
    {
        static $rid = 0; // task run ID
        $this->taskNew[$rid] = new Task($runId, $name, $dsc);
        $rid++;
    }

    public function tRes(int $rid, int $workerDsc, int $dsc, &$result)
    {
        $peer = $this->server->getPeerByDsc($dsc);
        // @todo empty name!
        $worker = $this->getWorkerByDsc($workerDsc);
        $task = $worker->getTask($rid);
        $task->setResult($result);
        $worker->removeTask($task); // release worker
        $this->commandDispatcher->create(ThreadResult::NAME, $peer)
            ->onError(function () {
                //todo
            })
            ->onSuccess(function () {
                //todo
            })
            ->run(ThreadResult::serializeTask($task));
        Log::log('I send res to ' . $peer->getDsc());
    }
}
