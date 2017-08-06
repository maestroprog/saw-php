<?php

namespace Maestroprog\Saw\Thread;

use Maestroprog\Saw\Thread\Creator\ThreadCreatorInterface;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizerInterface;

interface MultiThreadingInterface extends ThreadCreatorInterface, SynchronizerInterface
{

}
