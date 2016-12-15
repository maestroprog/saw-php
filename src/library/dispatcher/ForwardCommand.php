<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.12.2016
 * Time: 22:17
 */

namespace maestroprog\saw\library\dispatcher;


trait ForwardCommand
{
    private $forwardTo;

    private $forwardFrom;

    public function isForwarding(): bool
    {
        return !is_null($this->forwardTo);
    }

    public function getForwardedFrom(): int
    {
        return $this->forwardFrom;
    }

    public function setForwardedFrom(Command $command)
    {
        return $this;
    }

    public function isForwarded(): bool
    {
        return !is_null($this->forwardFrom);
    }

    public function getForwardTo(): int
    {
        return $this->forwardFrom;
    }

    public function setForwardTo(Command $command): self
    {
        $this->forwardFrom = $command->getId();
        return $this;
    }
}
