<?php

namespace Maestroprog\Saw\Thread;

use Esockets\Client;

/**
 * Контролируемый поток.
 * Объект используется в контроллере в качестве объекта,
 * управляющего выполнением потока на воркере и
 * связанным с ним.
 */
class ControlledThread extends AbstractThread
{
    private $threadFrom;

    public function __construct(int $id, string $applicationId, string $uniqueId, Client $threadFrom)
    {
        parent::__construct($id, $applicationId, $uniqueId);
        $this->threadFrom = $threadFrom;
    }

    public function run(): AbstractThread
    {
        $this->state = self::STATE_RUN;
        return $this;
    }

    /**
     * Клиент, который выполняет поток/от которого поступил поток на выполнение.
     *
     * @return Client
     */
    public function getThreadFrom(): Client
    {
        return $this->threadFrom;
    }
}
