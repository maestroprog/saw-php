<?php

class LoggingProtocol extends \Esockets\base\AbstractProtocol
{
    private $realProtocol;

    public function __construct(\Esockets\base\IoAwareInterface $provider)
    {
        parent::__construct($provider);
        $this->realProtocol = new \Esockets\protocol\Easy($provider);
    }

    public function returnRead()
    {
        $data = $this->realProtocol->returnRead();
        if (!is_null($data)) {
            \Esockets\debug\Log::log('I receive', var_export($data, true));
        }
        return $data;
    }

    public function onReceive(callable $callback): \Esockets\base\CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    public function send($data): bool
    {
        \Esockets\debug\Log::log('I send', var_export($data, true));
        return $this->realProtocol->send($data);
    }
}
