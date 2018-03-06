<?php

namespace Maestroprog\Saw\ValueObject;


final class SawEnv
{
    const WEB = 1;
    const CONTROLLER = 2;
    const WORKER = 3;

    private $environment;

    protected const ENV_MAP = [self::WEB => 'web', self::CONTROLLER => 'controller', self::WORKER => 'worker'];

    private function __construct(int $environment)
    {
        $this->environment = $environment;
    }

    public static function web(): self
    {
        return new self(self::WEB);
    }

    public static function controller(): self
    {
        return new self(self::CONTROLLER);
    }

    public static function worker(): self
    {
        return new self(self::WORKER);
    }

    public function isWorker(): bool
    {
        return $this->environment === self::WORKER;
    }

    public function isWeb(): bool
    {
        return $this->environment === self::WEB;
    }

    public function __toString(): string
    {
        return self::ENV_MAP[$this->environment];
    }
}
