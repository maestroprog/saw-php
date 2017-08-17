<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Command\AbstractCommand;
use Maestroprog\Saw\Command\ContainerOfCommands;
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
     * @return AbstractCommand
     * @throws \RuntimeException
     */
    public function runSync(AbstractCommand $command, int $timeout): AbstractCommand
    {
        $started = microtime(true);
        $this->send($command);
        do {
            $this->workDispatcher->work();
        } while (!$command->isAccomplished() && (microtime(true) - $started) < $timeout);
        return $command;
    }

    /**
     * Выполняет асинхронный запуск команды,
     * т.е. возвращает управление сразу же после отправки команды.
     *
     * @param AbstractCommand $command
     * @return void
     * @throws \RuntimeException
     */
    public function runAsync(AbstractCommand $command)
    {
        $this->send($command);
    }

    /**
     * Отправляет клиенту команду на выполнение.
     *
     * @param AbstractCommand $command
     * @throws \RuntimeException
     */
    private function send(AbstractCommand $command)
    {
        $cmdId = $this->generateId();
        $this->commands->add($cmdId, $command);
        if (!$command->getClient()->send([
            'command' => $command->getCommandName(),
            'state' => CommandDispatcher::STATE_RUN,
            'id' => $cmdId,
            'code' => CommandDispatcher::CODE_VOID,
            'data' => $command->toArray()
        ])) {
            throw new \RuntimeException(sprintf('Fail run command "%s".', $command->getCommandName()));
        }
    }

    private function generateId(): int
    {
        static $id = 0;
        return ++$id;
    }
}
