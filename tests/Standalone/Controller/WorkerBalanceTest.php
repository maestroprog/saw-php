<?php

namespace tests\Standalone\Controller {

    use Esockets\Client;
    use Maestroprog\Saw\Command\ContainerOfCommands;
    use Maestroprog\Saw\Connector\ControllerConnectorInterface;
    use Maestroprog\Saw\Entity\Worker;
    use Maestroprog\Saw\Service\Commander;
    use Maestroprog\Saw\Thread\StubThread;
    use PHPUnit\Framework\TestCase;
    use Maestroprog\Saw\Command\AbstractCommand;
    use Maestroprog\Saw\Command\WorkerAdd;
    use Maestroprog\Saw\Command\WorkerDelete;
    use Maestroprog\Saw\Service\CommandDispatcher;
    use Maestroprog\Saw\Service\WorkerStarter;
    use Maestroprog\Saw\Standalone\Controller\WorkerBalance;
    use Maestroprog\Saw\Standalone\Controller\WorkerPool;
    use Maestroprog\Saw\ValueObject\ProcessStatus;

    /**
     * @covers \Maestroprog\Saw\Standalone\Controller\WorkerBalance
     */
    class WorkerBalanceTest extends TestCase
    {
        private static $connector;

        public static function setUpBeforeClass()
        {
            self::$connector = new class implements ControllerConnectorInterface
            {
                public function getCommandDispatcher(): CommandDispatcher
                {
                    // TODO: Implement getCommandDispatcher() method.
                }

                public function connect()
                {
                    // TODO: Implement connect() method.
                }

                public function getClient(): Client
                {
                    // TODO: Implement getClient() method.
                }

                public function work()
                {
                    // TODO: Implement work() method.
                }

                public function send($data): bool
                {
                    // TODO: Implement send() method.
                }
            };
        }

        /**
         * Тестирование корректной работы воркера.
         * План теста:
         * 1. Запуск пустого балансировщика
         * 2. Балансировщик запускает первый экземпляр воркера (ловим этот момент)
         * 3. Эмулируем успешное подключение воркера к контроллеру, подтверждение работы в балансировщике
         * 4. Эмулируем успешное завершение работы воркера
         */
        public function testWork()
        {
            /**
             * @var $workerStarter WorkerStarter|\PHPUnit_Framework_MockObject_MockObject
             */
            $workerStarter = $this->createMock(WorkerStarter::class);
            $commandDispatcher = new CommandDispatcher($cmdContainer = new ContainerOfCommands());
            $commander = new Commander(self::$connector, $cmdContainer);
            $workerPool = new WorkerPool();
            $balancer = new WorkerBalance($workerStarter, $commandDispatcher, $commander, $workerPool, 1);

            /**
             * @var $client Client|\PHPUnit_Framework_MockObject_MockObject
             */
            $client = $this->createMock(Client::class);
            $workerClient = clone $client;

            $processStatus = $this
                ->getMockBuilder(ProcessStatus::class)
                ->disableOriginalConstructor()
                ->getMock();
            $processStatus->method('getPid')->willReturn(1);
            $workerStarter
                ->method('start')
                ->willReturnCallback(
                    function () use ($processStatus) {
                        return $processStatus;
                    }
                );
//            $workerStarter->method('kill');
            $workerStarter->expects($this->once())->method('start');

            $client
                ->method('send')
                ->willReturnCallback(
                    function ($data) use ($commandDispatcher, $workerClient) {
                        $commandDispatcher->dispatch($data, $workerClient);
                        return true;
                    }
                );
            $addSuccess = false;
            $deleteSuccess = false;
            $workerClient
                ->method('send')
                ->willReturnCallback(
                    function ($data) use (&$addSuccess, &$deleteSuccess) {
                        switch ($data['command']) {
                            case WorkerAdd::NAME:
                                $addSuccess = true;
                            // no break
                            case WorkerDelete::NAME:
                                if ($addSuccess) {
                                    $deleteSuccess = true;
                                }
                                $this->assertEquals(CommandDispatcher::STATE_RES, $data['state']);
                                $this->assertEquals(CommandDispatcher::CODE_SUCCESS, $data['code']);
                                break;
                        }
                        return true;
                    }
                );

            $client->expects($this->exactly(2))->method('send');
            $workerClient->expects($this->exactly(2))->method('send');

            // 1. Запуск пустого балансировщика
            // 2. Балансировщик запускает первый экземпляр воркера (ловим этот момент)
            $balancer->work();

            // 3. Эмулируем успешное подключение воркера к контроллеру, подтверждение работы в балансировщике

            $commander->runAsync(new WorkerAdd($client, 1));

            $this->assertTrue($workerPool->isExistsById(0), 'Worker with id = 0 must be exists!');
            $this->assertEquals(1, $workerPool->getById(0)->getProcessResource()->getPid());

            // 4. Эмулируем успешное завершение работы воркера
            $commander->runAsync(new WorkerDelete($client));
            $this->assertEmpty($workerPool);

            $this->assertTrue($addSuccess, 'WorkerAdd must be success run.');
            $this->assertTrue($deleteSuccess, 'WorkerDelete must be success run.');
        }

        /**
         * Тестирование корректной работы метода для получения наименее нагруженного воркера для выполнения потока.
         */
        public function testGetLowLoadedWorker()
        {
            /** @var $workerStarter WorkerStarter|\PHPUnit_Framework_MockObject_MockObject */
            $workerStarter = $this->createMock(WorkerStarter::class);
            $commandDispatcher = new CommandDispatcher($cmds = new ContainerOfCommands());
            $commander = new Commander(self::$connector, $cmds);
            $workerPool = new WorkerPool();
            $balancer = new WorkerBalance($workerStarter, $commandDispatcher, $commander, $workerPool, 1);

            /** @var ProcessStatus|\PHPUnit_Framework_MockObject_MockObject $processStatus */
            $processStatus = $this
                ->getMockBuilder(ProcessStatus::class)
                ->disableOriginalConstructor()
                ->getMock();
            $processStatus->method('getPid')->willReturn(1);
            $processStatus2 = clone $processStatus;
            $processStatus2->method('getPid')->willReturn(2);
            /**
             * @var $client Client|\PHPUnit_Framework_MockObject_MockObject
             */
            $client = $this->createMock(Client::class);

            $worker1 = new Worker($processStatus, $client);
            $worker2 = new Worker($processStatus2, $client);

            $thread = new StubThread(1, 'test', 'test');

            $balancer->work();
            $balancer->addWorker($worker1);
            $balancer->addWorker($worker2);

            $worker1->addThreadToKnownList($thread);
            $worker2->addThreadToKnownList($thread);

            $worker1->addThreadToRunList($thread);
            $worker1->addThreadToRunList($thread);
            $worker2->addThreadToRunList($thread);

            $worker = $balancer->getLowLoadedWorker($thread);
            $this->assertEquals($worker2, $worker);
        }

        /**
         * Тестирование поведения балансировщика при некорректном запуске воркера.
         */
        public function testIncorrectWork()
        {
            /** @var ProcessStatus|\PHPUnit_Framework_MockObject_MockObject $processStatus */
            $processStatus = $this
                ->getMockBuilder(ProcessStatus::class)
                ->disableOriginalConstructor()
                ->getMock();
            $processStatus->expects($this->once())->method('kill');
            $processStatus->method('isRunning')->willReturn(true);
            /**
             * @var $workerStarter WorkerStarter|\PHPUnit_Framework_MockObject_MockObject
             */
            $workerStarter = $this->createMock(WorkerStarter::class);
            $workerStarter->expects($this->once())->method('start')->willReturn($processStatus);
            $commandDispatcher = new CommandDispatcher($cmds = new ContainerOfCommands());
            $commander = new Commander(self::$connector, $cmds);
            $workerPool = new WorkerPool();
            $balancer = new WorkerBalance($workerStarter, $commandDispatcher, $commander, $workerPool, 1);

            $balancer->work();
            $runningProperty = (new \ReflectionObject($balancer))->getProperty('running');
            $runningProperty->setAccessible(true);
            $runningProperty->setValue($balancer, time() - 11);

            $balancer->work();
        }
    }
}
