<?php

class LoggingProtocol extends \Esockets\base\AbstractProtocol implements \Esockets\base\PingSupportInterface
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
        if (!is_null($data)) {
//            \Esockets\debug\Log::log('I receive from ' . spl_object_hash($this->provider), var_export($data, true));
        }
        return $data;
    }

    public function onReceive(callable $callback): \Esockets\base\CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    public function send($data): bool
    {
//        \Esockets\debug\Log::log('I send to ' . spl_object_hash($this->provider), var_export($data, true));
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
