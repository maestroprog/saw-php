<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 30.11.16
 * Time: 22:41
 */

namespace maestroprog\saw\command;

use maestroprog\saw\library\Command;

class WorkerDelete extends Command
{
    const NAME = 'wdel';

    public function getData() : array
    {
        return [];
    }

    public function getCommand() : string
    {
        return self::NAME;
    }

    public function handle($data)
    {
        // TODO: Implement handle() method.
    }

}