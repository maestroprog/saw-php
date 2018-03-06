<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

class ApplicationRun extends AbstractCommand
{
    private $applicationId;

    public function __construct(Client $client, string $applicationId)
    {
        parent::__construct($client);

        $this->applicationId = $applicationId;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['application_id']);
    }

    public function toArray(): array
    {
        return ['application_id' => $this->applicationId];
    }
}
