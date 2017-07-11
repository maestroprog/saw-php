<?php

namespace Saw\Command;

final class DebugData extends AbstractCommand
{
    const NAME = 'dbgd';

    const TYPE_VALUE = 'value';
    const TYPE_STREAM = 'stream';
    const TYPE_INTERACTIVE = 'interactive';

    public $needData = ['type', 'result'];

    public function getType(): string
    {
        return $this->data['type'];
    }

    public function getResult()
    {
        switch (gettype($this->data['result'])) {
            case 'array':
                $result = '';
                foreach ($this->data['result'] as $key => $val) {
                    $result .= sprintf("%s: %s" . PHP_EOL, $key, $val);
                }
                return $result;

            // no break
            default:
                return $this->data['result'];
        }
    }
}
