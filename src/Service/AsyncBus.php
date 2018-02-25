<?php

namespace Maestroprog\Saw\Service;

class AsyncBus implements \Iterator
{
    public const SIGNAL_PAUSE = 'pause';

    /**
     * @var \Generator[]
     */
    private $generators = [];

    public function attachGenerator(\Generator $generator): void
    {
        if (!$generator->valid()) {
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
