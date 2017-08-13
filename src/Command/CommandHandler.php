<?php

namespace Maestroprog\Saw\Command;

final class CommandHandler
{
    private $name;
    private $class;
    private $callbackHandler;

    /**
     * CallbackHandler должен вернуть true если команда выполнена успешно,
     * и false если выполнить команду не удалось.
     *
     * @param string $class
     * @param callable|null $callbackHandler
     * @internal param string $name
     */
    public function __construct(string $class, callable $callbackHandler)
    {
        if (!is_subclass_of($class, AbstractCommand::class)) {
            throw new \InvalidArgumentException('Invalid command class "' . $class . '".');
        }
        /**
         * @var $class AbstractCommand
         */
        $this->name = $class::NAME;
        $this->class = $class;
        $this->callbackHandler = $callbackHandler;
    }

    /**
     * Вызывает callback handler для выполнения команды.
     *
     * @param AbstractCommand $context
     * @return bool
     * @throws \RuntimeException
     */
    public function exec(AbstractCommand $context): bool
    {
        return call_user_func($this->callbackHandler, $context);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
