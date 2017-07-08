<?php

namespace tests\Standalone\Controller;

use Esockets\Client;
use Esockets\dummy\DummyConnectionResource;
use PHPUnit\Framework\TestCase;
use Saw\Command\AbstractCommand;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Entity\Worker;
use Saw\Service\CommandDispatcher;
use Saw\Standalone\Controller\ThreadDistributor;
use Saw\Standalone\Controller\WorkerBalance;
use Saw\Standalone\Controller\WorkerPool;
use Saw\Thread\StubThread;

class ThreadDistributorTest extends TestCase
{
    /**
     * Тест, эмулирующий все этапы балансировки потока:
     * 1. Постановка потока в очередь
     * 2. Нахождение свободного воркера
     * 3. Постановка ему потока на выполнение
     * 4. Ожидание результата выполнения потока
     * 5. Принятие, обработка результата выполнения потка
     * 6. Отправка результата выполнения потока
     */
    public function testWork()
    {
        $threadCodes = [
            'TEST1' => function ($arg1, $arg2) {
                return $arg1 + $arg2;
            }
        ];

        $commandDispatcher = new CommandDispatcher();
        $workerPool = new WorkerPool();
        /**
         * @var $workerBalance WorkerBalance|\PHPUnit_Framework_MockObject_MockObject
         */
        $workerBalance = $this->getMockBuilder(WorkerBalance::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLowLoadedWorker'])
            ->getMock();

        /**
         * @var $workerClient Client|\PHPUnit_Framework_MockObject_MockObject
         */
        $workerClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['send', 'live', 'read', 'getConnectionResource'])
            ->getMock();

        // клиент controller => worker
        $controllerClient = clone $workerClient;
        $controllerClient->method('getConnectionResource')
            ->willReturn(new class extends DummyConnectionResource
            {
                public function getResource()
                {
                    return 1;
                }
            });
        // клиент web => controller
        $webClient = clone $workerClient;
        // клиент controller => web
        $controllerWebClient = clone $webClient;

        $worker = $this->getMockBuilder(Worker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $worker->method('getClient')->willReturn($controllerClient);
        $worker->method('getId')->willReturn(1);

        $workerPool->add($worker);

        $workerBalance->method('getLowLoadedWorker')
            ->willReturn($worker);

//        $workerClient->method('read')

        $threadDistributor = new ThreadDistributor(
            $commandDispatcher,
            $workerPool,
            $workerBalance
        );

        /**
         * @var $delayRun callable[]
         */
        $delayRun = [];

        $webClient->method('send')->willReturnCallback(
            function ($data) use ($commandDispatcher, $controllerWebClient) {
                $commandDispatcher->dispatch($data, $controllerWebClient);
                return true;
            }
        );

        $controllerWebClient->method('send')
            ->willReturnCallback(function ($data) use ($webClient) {
                switch ($data['command']) {
                    case ThreadRun::NAME:
                        // ожидается, что поток успешно запущен
                        $this->assertEquals(AbstractCommand::STATE_RES, $data['state']);
                        $this->assertEquals(AbstractCommand::RES_SUCCESS, $data['code']);
                        break;
                    case ThreadResult::NAME:
                        // ожидается, что результат выполнения потока успешный, и равен 3 (1 + 2)
                        $this->assertEquals(AbstractCommand::STATE_RUN, $data['state']);
                        $this->assertEquals(AbstractCommand::RES_VOID, $data['code']);
                        $this->assertEquals(3, $data['data']['result']);

                        // сообщим контроллеру, что результат успешно обработан
                        $command = AbstractCommand::create(
                            ThreadResult::class,
                            $data['id'],
                            $webClient
                        );
                        $command->handle($data);
                        $command->success();
                        break;
                }
                return true;
            });

        // воркер принимает поток на выполнение, выполняет его, и отправляет результат

        $controllerClient->method('send')
            ->willReturnCallback(function ($data) use ($threadCodes, $workerClient, &$delayRun) {
                switch ($data['command']) {
                    case ThreadRun::NAME:
                        // ожидается, что пришла команда на выполнение нового потока с аргументами
                        $this->assertEquals(AbstractCommand::STATE_RUN, $data['state']);
                        $this->assertContains([1, 2], $data['data'], 'Thread must contain arguments!');
                        $thread = new StubThread(
                            $data['data']['run_id'],
                            $data['data']['application_id'],
                            $data['data']['unique_id']
                        );
                        // сообщим контроллеру об успешном запуске потока
                        $command = AbstractCommand::instance(
                            ThreadRun::class,
                            1,
                            $workerClient,
                            AbstractCommand::STATE_RES,
                            AbstractCommand::RES_SUCCESS
                        );
                        $command->handle($data);
                        $command->success();

                        // выполним код потока
                        $result = call_user_func_array($threadCodes[$data['data']['unique_id']], $data['data']['arguments']);
                        $thread->setResult($result);
                        $delayRun[] = function () use ($workerClient, $thread) {
                            AbstractCommand::create(ThreadResult::class, 1, $workerClient)
                                ->run(ThreadResult::serializeTask($thread));
                        };
                        break;
                    case ThreadResult::NAME:
                        // ожидается, что результат выполнения потока успешно получен
                        $this->assertEquals(AbstractCommand::STATE_RES, $data['state']);
                        $this->assertEquals(AbstractCommand::RES_SUCCESS, $data['code']);
                        break;
                }
                return true;
            });

        $workerClient->method('send')
            ->willReturnCallback(function ($data) use ($commandDispatcher, $controllerClient) {
                $commandDispatcher->dispatch($data, $controllerClient);
                return true;
            });

        $workerBalance->expects($this->once())->method('getLowLoadedWorker');
        $webClient->expects($this->exactly(2))->method('send');
        $controllerWebClient->expects($this->exactly(2))->method('send');
        $workerClient->expects($this->exactly(2))->method('send');
        $controllerClient->expects($this->exactly(2))->method('send');

        // 0. Подготовили распределитель потока, работаем вхолостую
        $threadDistributor->work();

        // воркер должен выполнить код потока "TEST1"
        $testThread = (new StubThread(1, 'empty', 'TEST1'))
            ->setArguments([1, 2]);

        // 1. Принятие потока и постановка его в очередь.
        AbstractCommand::create(ThreadRun::class, 1, $webClient)
            ->run(ThreadRun::serializeThread($testThread));

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
    }
}
