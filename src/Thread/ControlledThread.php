<?php

namespace Saw\Thread;

/**
 * Контролируемый поток.
 * Объект используется в контроллере в качестве объекта,
 * управляющего выполнением потока на воркере и
 * связанным с ним.
 */
class ControlledThread extends AbstractThread
{
    public function __construct(int $id, string $applicationId, string $uniqueId)
    {
        parent::__construct($id, $applicationId, $uniqueId);
    }

    public function run(): AbstractThread
    {
        $this->state = self::STATE_RUN;
        return $this;
    }
}
