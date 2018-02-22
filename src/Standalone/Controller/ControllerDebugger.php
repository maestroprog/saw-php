<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\Client;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\DebugCommand;
use Maestroprog\Saw\Command\DebugData;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Service\ControllerRunner;

class ControllerDebugger
{
    private $commandDispatcher;
    private $commander;
    private $threadDistributor;
    private $runner;
    private $debuggerClient;

    public function __construct(
        CommandDispatcher $commandDispatcher,
        Commander $commander,
        ThreadDistributor $threadDistributor,
        ControllerRunner $runner
    )
    {
        $this->commandDispatcher = $commandDispatcher;
        $this->commander = $commander;
        $this->threadDistributor = $threadDistributor;
        $this->runner = $runner;

        $commandDispatcher->addHandlers([
            new CommandHandler(DebugCommand::class, function (DebugCommand $context) {
                $data = $this->query($context);
                $this->commander->runAsync(DebugData::fromArray($data, $context->getClient()));
            }),
            new CommandHandler(DebugData::class, function (DebugData $context) {
                if ($this->debuggerClient instanceof Client) {
                    $this->commander->runAsync(DebugData::fromArray($context->toArray(), $this->debuggerClient));
                }
            }),
        ]);
    }

    public function query(DebugCommand $query): array
    {
        $result = ['type' => DebugData::TYPE_VALUE];
        switch ($query->getQuery()) {
            case 'killall':
                $result['result']['Killed'] = $this->killAll();
                break;
            case 'top':
                $result['result'] = [
                    'Workers count' => $this->threadDistributor->getWorkerPool()->count(),
                    'Run threads count' => $this->threadDistributor->getThreadRunQueue()->count(),
                    'Known threads count' => $this->threadDistributor->getThreadKnownIndex()->count(),
                    'Currently queue size' => $this->threadDistributor->getThreadRunQueue()->count(),
                ];
                break;
            case 'fullstat':
                $result['result'] = $this->fullStat($query);
                break;
            case 'help':
                $result['result'] = 'List of commands: killall, fullstat, help, restart, stop';
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'restart':
                register_shutdown_function(function () {
                    $this->runner->start();
                });
            // no break
            case 'stop':
                $this->killAll();
                exit;
            // no break
            default:
                $result['result'] = 'Unknown query';
        }
        return $result;
    }

    protected function killAll(): int
    {
        error_log('будет убито ' . $this->threadDistributor->getWorkerPool()->count());
        $count = 0;
        $workerBalance = $this->threadDistributor->getWorkerBalance();
        foreach ($this->threadDistributor->getWorkerPool() as $worker) {
            $workerBalance->removeWorker($worker);
            $count++;
        }
        error_log('убито ' . $count);
        return $count;
    }

    protected function fullStat(DebugCommand $command): array
    {
        $this->debuggerClient = $command->getClient();
        foreach ($this->threadDistributor->getWorkerPool() as $worker) {
            $this->commander->runAsync(new DebugCommand($worker->getClient(), 'fullstat'));
        }
        return [
            'Workers count' => $this->threadDistributor->getWorkerPool()->count(),
            'Run threads count' => $this->threadDistributor->getThreadRunQueue()->count(),
            'Known threads count' => $this->threadDistributor->getThreadKnownIndex()->count(),
            'Currently queue size' => $this->threadDistributor->getThreadRunQueue()->count(),
            'Sources count' => $this->threadDistributor->getThreadRunSources()->count(),
            'Linked count' => $this->threadDistributor->getThreadLinks()->count(),
            'Work count' => $this->threadDistributor->getThreadRunWork()->count(),
        ];
    }
}
