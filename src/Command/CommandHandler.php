<?php

namespace Maestroprog\Saw\Command;

final class CommandHandler
{
    private $name;
    private $class;
    private $callbackHandler;

    /**
     * Если $callbackHandler не задан, то это означает,
     * что данная команда не будет выполняться,
     * но будет отправляться другим,
     * и принимать какие-то результаты выполнения.
     *
     * $callbackHandler должен вернуть true если команда выполнена успешно,
     * и false если выполнить команду не удалось.
     *
     * @param string $class
     * @param callable|null $callbackHandler
     * @internal param string $name
     */
    public function __construct(string $class, callable $callbackHandler = null)
    {
        if (!is_subclass_of($class, AbstractCommand::class)) {
            throw new \InvalidArgumentException('Invalid command class "' . $class . '"');
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
     * @return mixed|void
     */
    public function exec(AbstractCommand $context)
    {
        return $this->isExecutable() ? call_user_func($this->callbackHandler, $context) : (var_dump($this->getName()));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function isExecutable(): bool
    {
        return $this->callbackHandler !== null;
    }
}
