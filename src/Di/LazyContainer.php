<?php

namespace Maestroprog\Saw\Di;

use Maestroprog\Saw\Helper\LazyInstance;
use Psr\Container\ContainerInterface;

class LazyContainer extends LazyInstance implements ContainerInterface
{
    public function get($id)
    {
        return parent::get($id);
    }

    public function has($id): bool
    {
        return parent::has($id);
    }
}
