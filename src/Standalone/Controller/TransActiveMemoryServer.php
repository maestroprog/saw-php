<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\Client;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\VariableLink;
use Maestroprog\Saw\Command\VariableRequest;
use Maestroprog\Saw\Command\VariableSearch;
use Maestroprog\Saw\Command\VariableUnlink;
use Maestroprog\Saw\Memory\MemoryInterface;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;

final class TransActiveMemoryServer implements MemoryInterface
{
    const SIZE_LIMITER = 1000;
    const LOCK_LIMITER = 100;
    const TIMEOUT = 1000; // 1 second

    private $dispatcher;
    private $commander;

    private $currentSize;

    private $clients;
    private $index;
    private $ccIndex;

    public function __construct(CommandDispatcher $dispatcher, Commander $commander)
    {
        $this->dispatcher = $dispatcher;
        $this->commander = $commander;

        $this->currentSize = self::SIZE_LIMITER;

        $this->clients = new \SplDoublyLinkedList();
        $this->index = new \ArrayObject();

        $this
            ->dispatcher
            ->addHandlers([
                new CommandHandler(VariableSearch::class, function (VariableSearch $context) {
                    $this->dispatchClient($context->getClient());

                    if ($context->isNoResult()) {
                        $result = $this->has($context->getKey());
                    } else {
                        $result = $this->read($context->getKey());
                    }
                    return $result;
                }),
                new CommandHandler(VariableLink::class, function (VariableLink $context) {
                    $this->dispatchClient($context->getClient());

                    return $this->write($context->getKey(), $this->ccIndex);
                }),
                new CommandHandler(VariableUnlink::class, function (VariableUnlink $context) {
                    $this->dispatchClient($context->getClient());

                    $this->remove($context->getKey());
                }),
            ]);
    }

    private function dispatchClient(Client $client)
    {
        $this->ccIndex = $client->getConnectionResource()->getId();
        if (!$this->clients->offsetExists($this->ccIndex)) {
            $this->clients->add($this->ccIndex, $client);

            $client->onDisconnect(function () use ($client) {
                // при отсоединении какого-либо клиента чистим его мусор
                $clientId = $client->getConnectionResource()->getId();
                try {
                    $this->clients->offsetUnset($clientId);
                } catch (\OutOfRangeException $e) {
                    ; // nothing
                } finally {
                    foreach ($this->index as $varName => $ccIndex) {
                        if ($clientId === $ccIndex) {
                            $this->index->offsetUnset($varName);
                        }
                    }
                }
            });
        }
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        $thereIs = $this->index->offsetExists($varName);
        return $thereIs;
    }

    public function read(string $varName, bool $withLocking = true)
    {
        if (!$this->index->offsetExists($varName)) {
            throw new \OutOfBoundsException('Cannot read undefined "' . $varName . '".');
        }
        $client = $this->index->offsetGet($varName);
        $cmd = $this->commander->runSync(
            (new VariableRequest($this->clients->offsetGet($client), $varName, false, $withLocking))
                ->onError(function (VariableRequest $context) {
                    // todo it or nothing
                }),
            self::TIMEOUT
        );
        if (!$cmd->isAccomplished()) {
            throw new \LogicException(sprintf('Command "%s" run not completed.', $cmd->getCommandName()));
        } elseif (!$cmd->isSuccessful()) {
            throw unserialize($cmd->getAccomplishedResult());
        }
        return $cmd->getAccomplishedResult();
    }

    public function write(string $varName, $variable): bool
    {
        $this->index->offsetSet($varName, $variable);
        return true;
    }

    public function remove(string $varName)
    {
        if (!$this->index->offsetExists($varName)) {
            throw new \OutOfBoundsException('Cannot remove undefined "' . $varName . '".');
        }
        $this->index->offsetUnset($varName);
    }

    public function list(string $prefix = null): array
    {
        // TODO: Implement list() method.
    }

    public function free()
    {
        // TODO: Implement free() method.
    }
}
