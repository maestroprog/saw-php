<?php

namespace Maestroprog\Saw\Service;

interface CommandDispatcherProviderInterface
{
    public function getCommandDispatcher(): CommandDispatcher;
}
