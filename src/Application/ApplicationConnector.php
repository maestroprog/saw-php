<?php

namespace Maestroprog\Saw\Application;

use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Qwerty\Application\ApplicationInterface;
use function Maestroprog\Saw\iterateGenerator;

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
        $thread = $this
            ->provider
            ->getThreadCreator()
            ->threadArguments(
                'main(' . $this->id . ')', function (...$argv) {
            },
                (array)$this->prepare()
            );

        $run = $this
            ->provider
            ->getThreadRunner()
            ->runThreads(
                ...
                $this->provider
                    ->getThreadPools()
                    ->getCurrentPool()
                    ->getThreads()
            );
        if (!$run) {
            throw new \RuntimeException('Cannot run the threads.');
        }
        try {
            iterateGenerator($this->provider->getSynchronizer()->synchronizeAll(), 5);
        } catch (\RuntimeException $e) {
            echo $e->getMessage();
        }

        echo $thread->getResult();
    }

    public function end()
    {
    }
}
