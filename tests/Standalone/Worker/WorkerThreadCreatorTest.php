<?php

namespace tests\Standalone\Worker;

use Esockets\Client;
use Saw\Application\ApplicationInterface;
use Saw\Saw;
use Saw\Service\CommandDispatcher;
use Saw\Standalone\Worker\WorkerThreadCreator;
use Saw\Thread\Pool\ContainerOfThreadPools;
use Saw\Thread\Pool\PoolOfUniqueThreads;

class WorkerThreadCreatorTest extends \PHPUnit_Framework_TestCase
{
    public function testWorkerSendKnowOfCreatedThread()
    {
        Saw::instance()->init(require __DIR__ . '/../../../sample/config/saw.php')->instanceWorker();
        $app = $this->createMock(ApplicationInterface::class);
        $app->method('getId')->willReturn('1');
        Saw::factory()->getApplicationContainer()->add($app);

        $poolsContainer = new ContainerOfThreadPools();
        $pool = new PoolOfUniqueThreads();
        $poolsContainer->add(1, $pool);
        $dispatcher = new CommandDispatcher();
        $client = $this->createMock(Client::class);
        $threadCreator = new WorkerThreadCreator($poolsContainer, $dispatcher, $client);

        $client->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $threadCreator->thread('TEST', function () {
        });
        return $threadCreator;
    }

    /**
     * @param $threadCreator WorkerThreadCreator
     * @depends testWorkerSendKnowOfCreatedThread
     */
    public function testNotAddCurrentlyAddedThread(WorkerThreadCreator $threadCreator)
    {
        $threadCreator->thread('TEST', function () {
        });
    }
}
