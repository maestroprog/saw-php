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
    const STATE_NEW = 0;
    const STATE_RUN = 1;
    const STATE_RES = 2;

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

    public function __construct(int $id, Net $peer, $state = self::STATE_NEW)
    {
        $this->id = $id;
        $this->peer = $peer;
        $this->state = $state;
    }

    public function

    abstract public function getCommand() : string;

    public function run($data = [])
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

    abstract public function handle($data);

    abstract public function handleResult($result);
}
