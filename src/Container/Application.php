<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 25.02.2017
 * Time: 22:39
 */

namespace maestroprog\saw\Container;


use maestroprog\saw\Application\ApplicationInterface as App;

final class Application
{
    private $apps = [];

    private $current;

    public function add(App $application)
    {

    }

    public function getCurrent(): App
    {
        return $this->current;
    }
}