<?php

namespace Maestroprog\Saw\Thread;

/**
 * Контролируемый поток.
 * Объект используется в контроллере в качестве объекта,
 * управляющего выполнением потока на воркере и
 * связанным с ним.
 */
class StubThread extends StatefulThread
{
    public function run(): AbstractThread
    {
        $this->state = StatefulThread::STATE_RUN;

        return $this;
    }
}
