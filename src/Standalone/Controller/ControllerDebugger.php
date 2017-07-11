<?php

namespace Saw\Standalone\Controller;

use Saw\Command\CommandHandler;
use Saw\Command\DebugCommand;
use Saw\Command\DebugData;
use Saw\Entity\Worker;
use Saw\Service\CommandDispatcher;

class ControllerDebugger
{
    private $commandDispatcher;
    private $threadDistributor;

    public function __construct(
        CommandDispatcher $commandDispatcher,
        ThreadDistributor $threadDistributor
    )
    {
        $this->commandDispatcher = $commandDispatcher;
        $this->threadDistributor = $threadDistributor;

        $commandDispatcher->add([
            new CommandHandler(DebugCommand::NAME, DebugCommand::class, function (DebugCommand $context) {
                $data = $this->query($context);
                $this->commandDispatcher->create(DebugData::NAME, $context->getPeer())
                    ->run($data);
                return true;
            }),
            new CommandHandler(DebugData::NAME, DebugData::class),
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
                $result['result'] = $this->fullStat();
                break;
            case 'help':
                $result['result'] = 'List of commands: killall, fullstat, help';
                break;
            default:
                $result['result'] = 'Unknown query';
        }
        return $result;
    }

    protected function killAll(): int
    {
        $count = 0;
        foreach ($this->threadDistributor->getWorkerPool() as $worker) {
            /**
             * @var Worker $worker
             */
            $this->threadDistributor->getWorkerBalance()->removeWorker($worker);
            $count++;
        }
        return $count;
    }

    protected function fullStat(): array
    {
        foreach ($this->threadDistributor->getWorkerPool() as $worker) {
            /**
             * @var Worker $worker
             */
            $worker->getClient();
        }
    }
}
