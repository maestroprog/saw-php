<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Command\AbstractCommand;
use Maestroprog\Saw\Command\ContainerOfCommands;
use Maestroprog\Saw\Command\PacketCommand;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;

final class Commander
{
    const WAIT_TIMEOUT = 30; // время ожидания завершения выполнения команды в секундах

    private $workDispatcher;
    private $commands;

    public function __construct(CycleInterface $workDispatcher, ContainerOfCommands $commands)
    {
        $this->workDispatcher = $workDispatcher;
        $this->commands = $commands;
    }

    /**
     * Выполняет синхронный запуск команды,
     * т.е. дожидается результата выполнения команды.
     *
     * @param AbstractCommand $command
     * @param int $timeout Время ожидания выполнения команды
     *
     * @return AbstractCommand
     * @throws \RuntimeException
     */
    public function runSync(AbstractCommand $command, int $timeout): AbstractCommand
    {
        $started = microtime(true);
        $this->send($command);
        $generator = $this->workDispatcher->work();
        do {
            if ($generator->valid()) {
                $generator->next();
            }
        } while (!$command->isAccomplished() && (microtime(true) - $started) < $timeout);

        return $command;
    }

    /**
     * Выполняет асинхронный запуск команды,
     * т.е. возвращает управление сразу же после отправки команды.
     *
     * @param AbstractCommand $command
     *
     * @return void
     * @throws \RuntimeException
     */
    public function runAsync(AbstractCommand $command): void
    {
        $this->send($command);
    }

    public function runPacket(AbstractCommand ...$commands): void
    {
        if (empty($commands)) {
            return;
        }
        $packet = [];
        foreach ($commands as $command) {
            $cmdId = $this->generateId();
            $this->commands->add($cmdId, $command);
            $packet[] = $this->serializeCommand($command, $cmdId);
        }
        assert(isset($command) && $command instanceof AbstractCommand);
        $this->send(new PacketCommand($command->getClient(), $packet));
    }

    /**
     * Отправляет клиенту команду на выполнение.
     *
     * @param AbstractCommand $command
     *
     * @throws \RuntimeException
     */
    private function send(AbstractCommand $command): void
    {
        $cmdId = $this->generateId();
        $this->commands->add($cmdId, $command);
        if (!$command->getClient()->send($this->serializeCommand($command, $cmdId))) {
            throw new \RuntimeException(sprintf('Fail run command "%s".', $command->getCommandName()));
        }
    }

    private function generateId(): int
    {
        static $id = 0;

        return ++$id;
    }

    /**
     * @param AbstractCommand $command
     * @param $cmdId
     *
     * @return array
     */
    private function serializeCommand(AbstractCommand $command, $cmdId): array
    {
        return [
            'command' => $command->getCommandName(),
            'state' => CommandDispatcher::STATE_RUN,
            'id' => $cmdId,
            'code' => CommandDispatcher::CODE_VOID,
            'data' => $command->toArray()
        ];
    }
}
