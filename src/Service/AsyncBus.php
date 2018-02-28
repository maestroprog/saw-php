<?php

namespace Maestroprog\Saw\Service;

use Esockets\Debug\Log;
use Maestroprog\Saw\Saw;

class AsyncBus implements \Iterator
{
    public const SIGNAL_PAUSE = 'pause';

    /**
     * @var \Generator[]
     */
    private $generators = [];

    public function attachGenerator(\Generator $generator): void
    {
        if (!$generator->valid()) { // valid() выполнит rewind() если генератор новый.
            throw new \InvalidArgumentException('Invalid generator given.');
        }

        $this->generators[] = $generator;
    }

    public function current(): string
    {
        return self::SIGNAL_PAUSE;
    }

    public function next(): void
    {
        $count = count($this->generators);
        for ($i = 0; $i < $count; $i++) {
            $generator = $this->generators[$i];

            do {

                if (Saw::isDebugEnabled()) {
                    Log::log($generator->key(), $generator->current());
                    usleep(50000);
                }

                if (!$generator->valid()) {
                    break;
                }
                $generator->next();
            } while ($generator->current() !== self::SIGNAL_PAUSE);
        }
    }

    public function key()
    {
        return __CLASS__;
    }

    public function valid(): bool
    {
        return !empty($this->generators);
    }

    public function rewind(): void
    {
        $this->next();
    }
}
