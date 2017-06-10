<?php

namespace Saw\Thread;

class ThreadWithCode extends AbstractThread
{
    /**
     * @var callable Содержит код потока.
     */
    private $code;

    public function __construct(int $id, string $uniqueId, callable $code)
    {
        parent::__construct($id, $uniqueId);
        $this->code = $code;
    }

    public function run()
    {
        {
            try {
                call_user_func_array($this->code, $this->arguments);
            } catch (\Throwable $throwable) {
                // todo
            }
        }
    }
}
