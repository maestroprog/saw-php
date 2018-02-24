<?php

namespace Maestroprog\Saw\Thread;

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
    /**
     * @var \Generator|null
     */
    private $generator;

    public function __construct(int $id, string $applicationId, string $uniqueId, callable $code)
    {
        parent::__construct($id, $applicationId, $uniqueId);

        $this->code = $code;
    }

    public function run(): \Generator
    {
        try {
            if (null === $this->generator) {
                $this->generator = call_user_func_array($this->code, $this->arguments);
            }
            if ($this->generator instanceof \Generator) {
                /* Генератор выполняет функцию асинхронной работы кода,
                 * в т.ч. асинхронная синхрониация вложенных потоков. */
                yield from $this->generator;
//                $this->generator->rewind();
//                while ($this->generator->valid()) {
//                    yield $this->generator->current();
//                    $this->generator->next();
//                }
                $result = $this->generator->getReturn();
            } else {
                $result = $this->generator;
            }
        } catch (\Throwable $e) {
            $result = new ThreadRunningException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $this->generator = null;
            $this->synchronized();

            if ($result instanceof ThreadRunningException) {
                throw $result;
            }

            return $result;
        }
    }
}
