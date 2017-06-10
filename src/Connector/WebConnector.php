<?php

namespace Saw\Connector;

use Esockets\base\SenderInterface;
use Esockets\Client;
use Esockets\debug\Log;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Saw;
use Saw\Service\CommandDispatcher;

/**
 * Коннектор, использующийся index.php скриптом, для подключения к контроллеру.
 */
final class WebConnector implements SenderInterface
{
    public $work = true;

    private $client;
    private $dispatcher;

    public function __construct(
        Client $client,
        CommandDispatcher $commandDispatcher
    )
    {
        if (!$client->isConnected()) {
            throw new \LogicException('The client must be connected.');
        }
        $this->client = $client;
        $client->onDisconnect(function () {
            $this->work = false;
        });
        $client->onReceive($this->onRead());

        $this->dispatcher = Saw::factory()->createCommandDispatcher($client, [
            new CommandHandler(ThreadRun::NAME, ThreadRun::class),
            new CommandHandler(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    try {
                        $task = $this->taskManager->getRunTask($context->getRunId());
                        $task->setResult($context->getResult());
                    } catch (\Throwable $e) {
                        $this->stop();
                        throw $e;
                    }
                }
            ),
        ]);
    }

    /**
     * Выполняет подключение к контроллеру.
     * Если контроллер не работает, выполняется запуск контроллера.
     *
     * @return Client
     * @throws \RuntimeException Если не удалось запустить контроллер
     */
    public function connect(): Client
    {
        $client = $this->getFactory()->getControllerClient();
        try {
            $client->connect($this->config['controller_address']);
        } catch (ConnectionException $e) {
            $this->getFactory()->getControllerStarter()->start($this->config['controller_address']);
        }
        return $client;
    }

    /**
     * @return CommandDispatcher
     */
    public function getDispatcher(): CommandDispatcher
    {
        return $this->dispatcher;
    }

    public function work()
    {
        while ($this->work) {
            $this->client->live();
        }
    }

    public function stop()
    {
        $this->work = false;
        $this->client->disconnect();
    }

    public function send($data): bool
    {
        return $this->client->send($data);
    }

    protected function onRead(): callable
    {
        return function ($data) {
            Log::log('I RECEIVED  :)');
            Log::log(var_export($data, true));

            switch ($data) {
                case 'HELLO':
                    $this->client->send('HELLO');
                    break;
                case 'ACCEPT':
                    // todo
                    break;
                case 'INVALID':
                    // todo
                    break;
                case 'BYE':
                    $this->work = false;
                    break;
                default:
                    if (is_array($data) && $this->dispatcher->valid($data)) {
                        $this->dispatcher->dispatch($data, $this->client);
                    } else {
                        $this->client->send('INVALID');
                    }
            }
        };
    }
}
