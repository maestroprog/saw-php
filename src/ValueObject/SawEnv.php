<?php

namespace Maestroprog\Saw\ValueObject;


final class SawEnv
{
    const WEB = 1;
    const CONTROLLER = 2;
    const WORKER = 3;

    private $environment;

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

    private function __construct(int $environment)
    {
        if (!in_array($environment, [self::WEB, self::CONTROLLER, self::WORKER])) {
            throw new \InvalidArgumentException('Invalid environment value.');
        }
        $this->environment = $environment;
    }

    public function isWeb(): bool
    {
        return $this->environment === self::WEB;
    }

    public function isWorker(): bool
    {
        return $this->environment === self::WORKER;
    }
}
