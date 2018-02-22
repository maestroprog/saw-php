<?php

use Esockets\base\AbstractProtocol;
use Esockets\base\PingPacket;
use Esockets\base\PingSupportInterface;

class LoggingProtocol extends AbstractProtocol implements PingSupportInterface
{
    /**
     * @var \Esockets\protocol\EasyStream|\Esockets\protocol\EasyDataGram|AbstractProtocol
     */
    private $realProtocol;

    private static $realProtocolClass = \Esockets\protocol\EasyStream::class;

    public static function withRealProtocolClass(string $class): string
    {
        self::$realProtocolClass = $class;
        return static::class;
    }

    public function __construct(\Esockets\base\IoAwareInterface $provider)
    {
        parent::__construct($provider);
        $this->realProtocol = new self::$realProtocolClass($provider);
        $this->eventReceive->attachCallbackListener(function ($data) {
            $this->log('READING', $data);
        });
        $this->realProtocol->onPingReceived(function (PingPacket $ping) {
            $this->log('PING RECEIVED', $ping->getValue() . ' ' . ($ping->isResponse() ? 'pong' : 'ping'));
            $this->pingReceived($ping);
        });
    }

    public function returnRead()
    {
        $data = $this->realProtocol->returnRead();
        if ($data !== null) {
            $this->log('FORCED READING', $data);
        }
        return $data;
    }

    public function onReceive(callable $callback): \Esockets\base\CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    public function send($data): bool
    {
        $this->log('SENDING', $data);
        return $this->realProtocol->send($data);
    }

    public function ping(\Esockets\base\PingPacket $pingPacket): void
    {
        $this->logPingPong($pingPacket);
        $this->realProtocol->ping($pingPacket);
    }

    public function pong(callable $pongReceived): void
    {
        $this->realProtocol->pong(function (\Esockets\base\PingPacket $packet) use ($pongReceived) {
            $this->logPingPong($packet);
            $pongReceived($packet);
        });
    }

    private $pingCallback;

    /**
     * @inheritdoc
     */
    public function onPingReceived(callable $pingReceived): void
    {
        $this->pingCallback = $pingReceived;
    }

    private function pingReceived(PingPacket $ping): void
    {
        if (null !== $this->pingCallback) {
            call_user_func($this->pingCallback, $ping);
        }
    }

    /**
     * @param \Esockets\base\PingPacket $pingPacket
     */
    private function logPingPong(\Esockets\base\PingPacket $pingPacket): void
    {
        $this->log('PING', $pingPacket->getValue() . ' ' . ($pingPacket->isResponse() ? 'pong' : 'ping'));
    }

    protected function log($type, $data)
    {
        if (!defined('ENV') || ENV === 'WEB') {
            return;
        }
        echo sprintf(
            '[%s] (%s): %s:%s %s' . PHP_EOL,
            ENV,
            date('Y-m-d H:i:s'),
            $type,
            ($this->provider instanceof \Esockets\socket\AbstractSocketClient)
                ? $this->provider->getPeerAddress()
                : '',
            substr(json_encode($data, JSON_UNESCAPED_UNICODE), 0, 256)
        );
    }
}
