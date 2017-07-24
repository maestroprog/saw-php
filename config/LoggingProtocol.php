<?php

use Esockets\base\AbstractProtocol;
use Esockets\base\PingSupportInterface;

class LoggingProtocol extends AbstractProtocol implements PingSupportInterface
{
    private $realProtocol;

    public function __construct(\Esockets\base\IoAwareInterface $provider)
    {
        parent::__construct($provider);
        $this->realProtocol = new \Esockets\protocol\EasyStream($provider);
    }

    public function returnRead()
    {
        $data = $this->realProtocol->returnRead();
        return $data;
    }

    public function onReceive(callable $callback): \Esockets\base\CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    public function send($data): bool
    {
        return $this->realProtocol->send($data);
    }

    public function ping(\Esockets\base\PingPacket $pingPacket)
    {
        $this->realProtocol->ping($pingPacket);
    }

    public function pong(callable $pongReceived)
    {
        $this->realProtocol->pong($pongReceived);
    }
}
