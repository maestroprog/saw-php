<?php

namespace Saw\Thread\Runner;

interface ThreadRunnerDisablingSupportInterface extends ThreadRunnerInterface
{
    public function disable();

    public function enable();
}
