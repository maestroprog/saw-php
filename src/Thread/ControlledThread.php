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
    public function __construct(int $id, string $uniqueId)
    {
        parent::__construct($id, $uniqueId);
    }

    public function run()
    {
        // TODO: Implement run() method.
    }
}
