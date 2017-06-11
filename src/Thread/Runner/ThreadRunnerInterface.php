<?php

namespace Saw\Thread\Runner;

use Saw\Thread\MultiThreadingInterface;

interface ThreadRunnerInterface extends MultiThreadingInterface
{
    public function setResultByRunId(int $id, $data);
}
