<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 10:25
 */

namespace maestroprog\saw\library\dispatcher;

use maestroprog\esockets\base\Net;
use maestroprog\esockets\Peer;

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

    protected $data = [];

    protected $needData = [];

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

    public function __construct(int $id, Net $peer, $state = self::STATE_NEW, $code = self::RES_VOID)
    {
        $this->id = $id;
        $this->peer = $peer;
        $this->state = $state;
        $this->code = $code;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Возвращает состояние команды.
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return Net|Peer
     */
    public function getPeer(): Net
    {
        return $this->peer;
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
        if (!$this->isValid($data)) {
            throw new \Exception('Invalid command ' . $this->getCommand());
        }
        $this->state = self::STATE_RUN;
        if (!$this->peer->send([
            'command' => $this->getCommand(),
            'state' => $this->state,
            'id' => $this->id,
            'code' => $this->code,
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
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Возвращает имя команды.
     *
     * @return string
     */
    abstract public function getCommand(): string;

    /**
     * Инициализирует кастомные данные, поступившие вместе с командой.
     *
     * @param $data
     * @return void
     */
    public function handle(array $data)
    {
        $this->data = array_merge($this->data, array_intersect_key($data, array_flip($this->needData)));
    }

    /**
     * Выполняет необходимые проверки перед запуском задачи,
     * а именно - есть ли все необходимые данные для запуска задачи.
     *
     * todo final!
     * @return bool
     */
    protected function isValid(&$data): bool
    {
        return count(array_diff_key(array_flip($this->needData), $data)) === 0;
    }
}
