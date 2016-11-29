<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 29.11.16
 * Time: 10:58
 */

namespace maestroprog\saw\command;

use maestroprog\saw\library\Command;

class WorkerAdd extends Command
{
    const NAME = 'wadd';

    public function getCommand() : string
    {
        return self::NAME;
    }

    public function handle($data)
    {

    }

    public function handleResult($result)
    {
        // TODO: Implement handleResult() method.
    }
}
