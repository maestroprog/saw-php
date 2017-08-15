<?php

namespace tests\Standalone\Controller;

use Esockets\Client;
use Esockets\dummy\DummyConnectionResource;
use Maestroprog\Saw\Command\ContainerOfCommands;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;
use PHPUnit\Framework\TestCase;
use Maestroprog\Saw\Command\AbstractCommand;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Entity\Worker;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Standalone\Controller\ThreadDistributor;
use Maestroprog\Saw\Standalone\Controller\WorkerBalance;
use Maestroprog\Saw\Standalone\Controller\WorkerPool;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\StubThread;

/**
 * @covers \Maestroprog\Saw\Command\AbstractCommand
 * @covers \Maestroprog\Saw\Command\ThreadResult
 * @covers \Maestroprog\Saw\Command\ThreadRun
 * @covers \Maestroprog\Saw\Command\CommandHandler
 * @covers \Maestroprog\Saw\Entity\Worker
 * @covers \Maestroprog\Saw\Service\CommandDispatcher
 * @covers \Maestroprog\Saw\Standalone\Controller\ThreadDistributor
 * @covers \Maestroprog\Saw\Standalone\Controller\WorkerBalance
 * @covers \Maestroprog\Saw\Standalone\Controller\WorkerPool
 * @covers \Maestroprog\Saw\Thread\Pool\AbstractThreadPool
 * @covers \Maestroprog\Saw\Thread\AbstractThread
 * @covers \Maestroprog\Saw\Thread\ControlledThread
 * @covers \Maestroprog\Saw\Thread\StubThread
 * @covers \Maestroprog\Saw\Thread\Pool\ControllerThreadPoolIndex
 * @covers \Maestroprog\Saw\Thread\Pool\ThreadLinker
 */
class ThreadDistributorTest extends TestCase
{
    /**
     * Тест, эмулирующий все этапы балансировки потока:
     * 1. Постановка потока в очередь
     * 2. Нахождение свободного воркера
     * 3. Постановка ему потока на выполнение
     * 4. Ожидание результата выполнения потока
     * 5. Принятие, обработка результата выполнения потка
     * 6. Отправка результата выполнения потока.
     */
    public function testWork()
    {
        $threadCodes = [
            'TEST1' => function ($arg1, $arg2) {
                return $arg1 + $arg2;
            }
        ];

        $commandDispatcher = new CommandDispatcher($cmds = new ContainerOfCommands());
        $commander = new Commander(new class implements CycleInterface
        {
            public function work()
            {
                // TODO: Implement work() method.
            }
        }, $cmds);

        $workerPool = new WorkerPool();
        /**
         * @var $workerBalance WorkerBalance|\PHPUnit_Framework_MockObject_MockObject
         */
        $workerBalance = $this
            ->getMockBuilder(WorkerBalance::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLowLoadedWorker'])
            ->getMock();

        /**
         * @var $workerClient Client|\PHPUnit_Framework_MockObject_MockObject
         */
        $workerClient = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['send', 'live', 'read', 'getConnectionResource'])
            ->getMock();

        // клиент controller => worker
        $controllerClient = clone $workerClient;
        $controllerClient
            ->method('getConnectionResource')
            ->willReturn(new class extends DummyConnectionResource
            {
                public function getId(): int
                {
                    return 1;
                }
            });
        // клиент web => controller
        $webClient = clone $workerClient;
        // клиент controller => web
        $controllerWebClient = clone $webClient;

        $worker = $this->getMockBuilder(Worker::class)->disableOriginalConstructor()->getMock();
        $worker->method('getClient')->willReturn($controllerClient);
        $worker->method('getId')->willReturn(1);

        $workerPool->add($worker);

        $workerBalance->method('getLowLoadedWorker')->willReturn($worker);

//        $workerClient->method('read')

        $threadDistributor = new ThreadDistributor(
            $commandDispatcher,
            $commander,
            $workerPool,
            $workerBalance
        );

        /**
         * @var $delayRun callable[]
         */
        $delayRun = [];

        $webClient
            ->method('send')
            ->willReturnCallback(
                function ($data) use ($commandDispatcher, $controllerWebClient) {
                    $commandDispatcher->dispatch($data, $controllerWebClient);
                    return true;
                }
            );

        $controllerWebClient
            ->method('send')
            ->willReturnCallback(
                function ($data) use ($webClient) {
                    switch ($data['command']) {
                        case ThreadRun::NAME:
                            // ожидается, что поток успешно запущен
                            $this->assertEquals(CommandDispatcher::STATE_RES, $data['state']);
                            $this->assertEquals(CommandDispatcher::CODE_SUCCESS, $data['code']);
                            break;
                        case ThreadResult::NAME:
                            // ожидается, что результат выполнения потока успешный, и равен 3 (1 + 2)
                            $this->assertEquals(CommandDispatcher::STATE_RUN, $data['state']);
                            $this->assertEquals(CommandDispatcher::CODE_VOID, $data['code']);
                            $this->assertEquals(3, $data['data']['result']);

                            // сообщим контроллеру, что результат успешно обработан
//                            $command = ThreadResult::fromArray($data['data'], $webClient);
                            $webClient->send([
                                'id' => $data['id'],
                                'data' => [],
                                'command' => ThreadResult::NAME,
                                'code' => CommandDispatcher::CODE_SUCCESS,
                                'state' => CommandDispatcher::STATE_RES,
                            ]);
//                            $command->handle($data);
//                            $command->success();
                            break;
                    }
                    return true;
                }
            );

        // воркер принимает поток на выполнение, выполняет его, и отправляет результат

        $controllerClient
            ->method('send')
            ->willReturnCallback(
                function ($data) use ($threadCodes, $workerClient, &$delayRun) {
                    switch ($data['command']) {
                        case ThreadRun::NAME:
                            // ожидается, что пришла команда на выполнение нового потока с аргументами
                            $this->assertEquals(CommandDispatcher::STATE_RUN, $data['state']);
                            $this->assertContains([1, 2], $data['data'], 'Thread must contain arguments!');
                            $thread = new StubThread(
                                $data['data']['run_id'],
                                $data['data']['application_id'],
                                $data['data']['unique_id']
                            );
                            // сообщим контроллеру об успешном запуске потока
//                            $command = ThreadRun::fromArray($data['data'], $workerClient);
//                            $command->handle($data);
//                            $command->success();
                            $workerClient->send([
                                'id' => $data['id'],
                                'data' => [],
                                'command' => ThreadRun::NAME,
                                'code' => CommandDispatcher::CODE_SUCCESS,
                                'state' => CommandDispatcher::STATE_RES,
                            ]);

                            // выполним код потока
                            $result = call_user_func_array(
                                $threadCodes[$data['data']['unique_id']],
                                $data['data']['arguments']
                            );
                            $thread->setResult($result);
                            $delayRun[] = function () use ($workerClient, $thread) {
                                $workerClient->send([
                                    'id' => 1,
                                    'command' => ThreadResult::NAME,
                                    'state' => CommandDispatcher::STATE_RUN,
                                    'code' => CommandDispatcher::CODE_VOID,
                                    'data' => (new ThreadResult(
                                        $workerClient,
                                        $thread->getId(),
                                        $thread->getApplicationId(),
                                        $thread->getUniqueId(),
                                        $thread->getResult()
                                    ))->toArray()
                                ]);
                            };
                            break;
                        case ThreadResult::NAME:
                            // ожидается, что результат выполнения потока успешно получен
                            $this->assertEquals(CommandDispatcher::STATE_RES, $data['state']);
                            $this->assertEquals(CommandDispatcher::CODE_SUCCESS, $data['code']);
                            break;
                    }
                    return true;
                }
            );

        $workerClient
            ->method('send')
            ->willReturnCallback(
                function ($data) use ($commandDispatcher, $controllerClient) {
                    $commandDispatcher->dispatch($data, $controllerClient);
                    return true;
                }
            );

        $workerBalance->expects($this->once())->method('getLowLoadedWorker');
        $webClient->expects($this->exactly(2))->method('send');
        $controllerWebClient->expects($this->exactly(2))->method('send');
        $workerClient->expects($this->exactly(2))->method('send');
        $controllerClient->expects($this->exactly(2))->method('send');

        // 0. Подготовили распределитель потока, работаем вхолостую
        $threadDistributor->work();

        // воркер должен выполнить код потока "TEST1"
        $testThread = (new StubThread(1, 'empty', 'TEST1'))->setArguments([1, 2]);

        // 1. Принятие потока и постановка его в очередь.
        $webClient->send([
            'id' => 1,
            'command' => ThreadRun::NAME,
            'state' => CommandDispatcher::STATE_RUN,
            'code' => CommandDispatcher::CODE_VOID,
            'data' => (new ThreadRun(
                $webClient,
                $testThread->getId(),
                $testThread->getApplicationId(),
                $testThread->getUniqueId(),
                $testThread->getArguments()
            ))->toArray()
        ]);

        // 2. Нахождение свободного воркера
        // 3. Постановка ему потока на выполнение
        $threadDistributor->work();
        array_walk($delayRun, function (callable $callback, $key) use (&$delayRun) {
            $callback();
            unset($delayRun[$key]);
        });
        // 4. Ожидание результата выполнения потока
        // 5. Принятие, обработка результата выполнения потка
        // 6. Отправка результата выполнения потока
        $threadDistributor->work();

        /**
         * @var $threadRunWork AbstractThreadPool
         */
        $threadRunWork = $this->readAttribute($threadDistributor, 'threadRunWork');
        $this->assertCount(0, $threadRunWork);
    }
}
