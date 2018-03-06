<?php

namespace Maestroprog\Saw\Thread;

use Esockets\Debug\Log;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizationThreadInterface;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizationTrait;

/**
 * @method self setArguments(array $arguments);
 */
class ThreadWithCode extends AbstractThread implements SynchronizationThreadInterface
{
    use SynchronizationTrait;

    /**
     * @var callable Содержит код потока.
     */
    private $code;

    public function __construct(int $id, string $applicationId, string $uniqueId, callable $code)
    {
        parent::__construct($id, $applicationId, $uniqueId);

        $this->code = $code;
    }

    public function run(): \Generator
    {
        try {
            $generator = call_user_func_array($this->code, $this->arguments);
            if ($generator instanceof \Generator) {
                /* Генератор выполняет функцию асинхронной работы кода,
                 * в т.ч. асинхронная синхрониация вложенных потоков. */
                yield from $generator;

                $result = $generator->getReturn();
            } else {
                $result = $generator;
            }
        } catch (\Throwable $e) {
            Log::log($e->getMessage());
            $result = new ThreadRunningException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $result = $result ?? null;

            if ($result instanceof ThreadRunningException) {
                throw $result;
            }

            return $result;
        }
    }

    public function setResult($data): void
    {
        parent::setResult($data);

        $this->synchronized();
    }
}
