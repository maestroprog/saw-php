<?php

namespace Maestroprog\Saw\Helper;

class LazyInstance
{
    private $instantiation;
    private $instance;

    public function __construct(callable $instantiation)
    {
        $this->instantiation = $instantiation;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!$this->instance) {
            $this->instantiate();
        }

        return call_user_func_array([$this->instance, $name], $arguments);
    }

    protected function instantiate(): void
    {
        $this->instance = call_user_func($this->instantiation);
    }
}
