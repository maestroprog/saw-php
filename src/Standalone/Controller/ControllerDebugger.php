<?php

namespace Saw\Service;

use Saw\Command\CommandHandler;
use Saw\Command\DebugCommand;

class ControllerDebugger
{
    public function __construct(
        CommandDispatcher $commandDispatcher
    )
    {
        $commandDispatcher->add([
            new CommandHandler(DebugCommand::NAME, DebugCommand::class, function (DebugCommand $context) {
                $this->query($context->getQuery());
                return true;
            })
        ]);
    }

    public function query(string $query)
    {

    }
}
