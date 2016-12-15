<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 15.12.16
 * Time: 19:44
 */

namespace maestroprog\saw\command;


use maestroprog\saw\library\Command;

class TaskDone extends Command
{
    const NAME = 'tend';

    private $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function getCommand(): string
    {
        return self::NAME;
    }

    public function handle(array $data)
    {
        // TODO: Implement handle() method.
    }

    public function isValid(): bool
    {
        return isset($this->data['result']);
    }

}
