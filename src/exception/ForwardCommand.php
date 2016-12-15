<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.12.2016
 * Time: 21:59
 */

namespace maestroprog\saw\exception;

use maestroprog\esockets\base\Net;

class ForwardCommand extends \Exception
{
    public function __construct(Net $peer)
    {
    }
}
