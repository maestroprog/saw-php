<?php

namespace Saw\Thread;

class ThreadWithCode extends AbstractThread
{
    /**
     * @var callable Содержит код потока.
     */
    private $code;

    public function __construct(int $id, string $applicationId, string $uniqueId, callable $code)
    {
        parent::__construct($id, $applicationId, $uniqueId);
        $this->code = $code;
    }

    public function run(): AbstractThread
    {
        try {
            $this->state = self::STATE_RUN;
            $result = call_user_func_array($this->code, $this->arguments);
        } catch (\Throwable $throwable) {
            // todo
            $result = null;
            throw new ThreadRunningException($throwable->getMessage(), $throwable->getCode(), $throwable);
        } finally {
            $this->setResult($result);
            $this->state = self::STATE_END;
        }
    }
}
