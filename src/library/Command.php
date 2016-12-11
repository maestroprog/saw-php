<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 10:25
 */

namespace maestroprog\saw\library;

use maestroprog\esockets\base\Net;

abstract class Command
{
    use CommandCode;

    const STATE_NEW = 0;
    const STATE_RUN = 1;
    const STATE_RES = 2;

    const RES_VOID = 0;
    const RES_SUCCESS = 1;
    const RES_ERROR = 2;

    const NAME = 'void';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Net
     */
    private $peer;

    /**
     * @var int
     */
    private $state;

    /**
     * @var int
     */
    private $code;

    public function __construct(int $id, Net $peer, $state = self::STATE_NEW)
    {
        $this->id = $id;
        $this->peer = $peer;
        $this->state = $state;
        $this->code = self::RES_VOID;
    }

    /**
     * Возвращает состояние команды.
     *
     * @return int
     */
    public function getState() : int
    {
        return $this->state;
    }

    /**
     * Отправляет команду на выполнение.
     *
     * @param array $data
     * @throws \Exception
     * @throws \Throwable
     */
    final public function run($data = [])
    {
        $this->state = self::STATE_RUN;
        if (!$this->peer->send([
            'command' => $this->getCommand(),
            'state' => $this->state,
            'id' => $this->id,
            'data' => $data])
        ) {
            throw new \Exception('Fail run command ' . $this->getCommand());
        }
    }

    /**
     * Отправляет результат выполнения команды.
     *
     * @throws \Exception
     * @throws \Throwable
     */
    final public function result()
    {
        $this->state = self::STATE_RES;
        if (!$this->peer->send([
            'command' => $this->getCommand(),
            'state' => $this->state,
            'id' => $this->id,
            'code' => $this->code,
            'data' => $this->getData()])
        ) {
            throw new \Exception('Fail for send result of command ' . $this->getCommand());
        }
    }

    /**
     * Возвращает кастомные данные, которые нужно передать вместе с командой.
     *
     * @return array
     */
    abstract public function getData() : array;

    /**
     * Возвращает имя команды.
     *
     * @return string
     */
    abstract public function getCommand() : string;

    /**
     * Инициализирует кастомные данные, поступившие вместе с командой.
     *
     * @param $data
     * @return mixed
     */
    abstract public function handle(array $data);
}
