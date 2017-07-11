<?php

namespace Saw\Thread;

use Esockets\Client;

/**
 * Контролируемый поток.
 * Объект используется в контроллере в качестве объекта,
 * управляющего выполнением потока на воркере и
 * связанным с ним.
 */
class StubThread extends AbstractThread
{
    public function run(): AbstractThread
    {
        $this->state = self::STATE_RUN;
        return $this;
    }
}
