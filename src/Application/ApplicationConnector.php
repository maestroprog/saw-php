<?php

namespace Maestroprog\Saw\Application;

use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Qwerty\Application\ApplicationInterface;

class ApplicationConnector implements ApplicationInterface
{
    private $id;
    private $provider;

    public function __construct(string $id, MultiThreadingProvider $provider)
    {
        $this->id = $id;
        $this->provider = $provider;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function init()
    {
    }

    public function prepare()
    {
        return [$_SERVER, $_GET, $_REQUEST, $_POST];
    }

    public function run()
    {

    }

    public function end()
    {

    }
}
