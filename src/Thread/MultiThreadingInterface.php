<?php

namespace Saw\Thread;

use Saw\Thread\Creator\ThreadCreatorInterface;
use Saw\Thread\Synchronizer\SynchronizerInterface;

interface MultiThreadingInterface extends ThreadCreatorInterface, SynchronizerInterface
{

}
