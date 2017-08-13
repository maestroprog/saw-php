<?php

namespace Maestroprog\Saw\Command;

interface AsyncCommandInterface
{
    /**
     * @return void
     */
    public function run();
}
