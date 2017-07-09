<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 09.07.2017
 * Time: 12:12
 */

namespace tests\Standalone\Controller {

    use Esockets\Client;
    use PHPUnit\Framework\TestCase;
    use Saw\Command\AbstractCommand;
    use Saw\Command\WorkerAdd;
    use Saw\Command\WorkerDelete;
    use Saw\Service\CommandDispatcher;
    use Saw\Service\WorkerStarter;
    use Saw\Standalone\Controller\WorkerBalance;
    use Saw\Standalone\Controller\WorkerPool;
    use Saw\ValueObject\ProcessStatus;

    class WorkerBalanceTest extends TestCase
    {
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
            $commandDispatcher = new CommandDispatcher();
            $workerPool = new WorkerPool();
            $balancer = new WorkerBalance($workerStarter, $commandDispatcher, $workerPool, 1);

            /**
             * @var $client Client|\PHPUnit_Framework_MockObject_MockObject
             */
            $client = $this->createMock(Client::class);
            $workerClient = clone $client;

            $workerStarter->method('start')
                ->willReturnCallback(function () {
                    return new ProcessStatus(true, 1);
                });
            $workerStarter->method('kill');
            $workerStarter->expects($this->once())->method('start');

            $client->method('send')
                ->willReturnCallback(function ($data) use ($commandDispatcher, $workerClient) {
                    $commandDispatcher->dispatch($data, $workerClient);
                    return true;
                });
            $addSuccess = false;
            $deleteSuccess = false;
            $workerClient->method('send')
                ->willReturnCallback(function ($data) use (&$addSuccess, &$deleteSuccess) {
                    switch ($data['command']) {
                        case WorkerAdd::NAME:
                            $addSuccess = true;
                        // no break
                        case WorkerDelete::NAME:
                            if ($addSuccess) {
                                $deleteSuccess = true;
                            }
                            $this->assertEquals(AbstractCommand::STATE_RES, $data['state']);
                            $this->assertEquals(AbstractCommand::RES_SUCCESS, $data['code']);
                            break;
                    }
                    return true;
                });

            $client->expects($this->exactly(2))->method('send');
            $workerClient->expects($this->exactly(2))->method('send');

            // 1. Запуск пустого балансировщика
            // 2. Балансировщик запускает первый экземпляр воркера (ловим этот момент)
            $balancer->work();

            // 3. Эмулируем успешное подключение воркера к контроллеру, подтверждение работы в балансировщике
            AbstractCommand::create(WorkerAdd::class, 1, $client)->run(['pid' => 1]);

            $this->assertTrue($workerPool->isExistsById(0), 'Worker with id = 0 must be exists!');
            $this->assertEquals(1, $workerPool->getById(0)->getProcessResource()->getPid());

            // 4. Эмулируем успешное завершение работы воркера
            AbstractCommand::create(WorkerDelete::class, 2, $client)->run();
            $this->assertEmpty($workerPool);

            $this->assertTrue($addSuccess, 'WorkerAdd must be success run.');
            $this->assertTrue($deleteSuccess, 'WorkerDelete must be success run.');
        }

        /**
         * Тестирование корректной работы метода для получения наименее нагруженного воркера для выполнения потока.
         */
        public function testGetLowLoadedWorker()
        {

        }

        /**
         * Тестирование поведения балансировщика при некорректном запуске воркера.
         */
        public function testIncorrectWork()
        {

        }
    }
}

namespace Saw\Service {

    class WorkerStarter
    {
        public function start()
        {

        }

        public function kill()
        {

        }
    }
}

namespace Saw\ValueObject {

    class ProcessStatus
    {
        private $pid;
        private $running;

        public function __construct(bool $running, int $pid)
        {
            $this->running = $running;
            $this->pid = $pid;
        }

        public function getPid(): int
        {
            return $this->pid;
        }

        public function isRunning(): bool
        {
            return $this->running;
        }
    }
}
