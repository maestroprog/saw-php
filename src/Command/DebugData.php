<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class DebugData extends AbstractCommand
{
    const NAME = 'dbgd';

    const TYPE_VALUE = 'value';
    const TYPE_STREAM = 'stream';
    const TYPE_INTERACTIVE = 'interactive';

    private $type;
    private $result;

    public function __construct(Client $client, string $type, $result)
    {
        parent::__construct($client);
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getResult()
    {
        switch (gettype($this->result)) {
            case 'array':
                $result = '';
                foreach ($this->result as $key => $val) {
                    $result .= sprintf("%s: %s" . PHP_EOL, $key, $val);
                }
                return $result;
            // no break

            default:
                return $this->result;
        }
    }

    public function toArray(): array
    {
        return ['type' => $this->type];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['type'], $data['result']);
    }
}
