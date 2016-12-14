<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 28.11.16
 * Time: 20:12
 */

namespace maestroprog\saw\entity;

class Task
{
    const NEW = 0; // новая задача
    const RUN = 1; // выполняемая задача
    const ERR = 2; // ошибка при выполнении

    /**
     * ID задачи.
     *
     * @var int
     */
    private $tid;

    /**
     * ID выполняемой задачи.
     *
     * @var int
     */
    private $rid;

    /**
     * Название задачи.
     *
     * @var string
     */
    private $name;

    /**
     * Peer ID воркера, которому поставлена задача.
     *
     * @var int
     */
    private $dsc;

    /**
     * Состояние выполнения задачи.
     *
     * @var int
     */
    private $state;

    /**
     * Результат выполнения задачи.
     *
     * @var mixed
     */
    private $result;

    public function __construct(int $tid, int $rid, string $name, int $dsc, int $state = self::NEW)
    {
        $this->tid = $tid;
        $this->rid = $rid;
        $this->name = $name;
        $this->dsc = $dsc;
        $this->state = $state;
    }

    public function getTaskId() : int
    {
        return $this->tid;
    }

    public function getRunId() : int
    {
        return $this->rid;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPeerDsc() : int
    {
        return $this->dsc;
    }

    public function getState() : int
    {
        return $this->state;
    }
}
