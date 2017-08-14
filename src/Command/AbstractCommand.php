<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;
use Maestroprog\Saw\Service\CommandDispatcher;

abstract class AbstractCommand
{
    const NAME = 'void';

    private $client;
    private $onSuccess;
    private $onError;

    private $accomplished = false;
    protected $accomplishedResult;

    final public static function isValidClass(string $class): bool
    {
        return is_subclass_of($class, AbstractCommand::class);
    }

    protected function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * С помощью позщнего статического связывания возвращает имя команды,
     * определённой в константе @see AbstractCommand::NAME текущего объекта,
     * которую можно переопределить.
     *
     * @return string
     */
    public function getCommandName(): string
    {
        return static::NAME;
    }

    /**
     * Вернёт клиента, от которого поступила команда/кому отправляется команда.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    public function isAccomplished(): bool
    {
        return $this->accomplished;
    }

    /**
     * @return mixed
     */
    public function getAccomplishedResult()
    {
        return $this->accomplishedResult;
    }

    /**
     * Возвращает кастомные данные, которые нужно передать вместе с командой.
     *
     * @return array
     * @deprecated
     */
    public function getData(): array
    {
        return $this->toArray();
    }

    /**
     * Метод вызывается диспетчером команд, и он реализует механизм вызова колбэков,
     * назначаемых в @see CommandCode::onSuccess() и @see CommandCode::onError().
     *
     * @param mixed $result
     * @param int $code
     * @return void
     * @throws \RuntimeException Исключение бросается в случае получения неизвестного статуса выполнения команды
     */
    public function dispatchResult($result, int $code)
    {
        $this->result = $result;
        $this->accomplished = true;
        switch ($code) {
            case CommandDispatcher::CODE_SUCCESS:
                if (is_callable($this->onSuccess)) {
                    call_user_func($this->onSuccess, $this);
                }
                break;
            case CommandDispatcher::CODE_ERROR:
                if (is_callable($this->onError)) {
                    call_user_func($this->onError, $this);
                }
                break;
            default:
                throw new \RuntimeException('Why is not success and is not error?');
        }
    }

    /**
     * Задает колбэк, вызывающийся при успешном выполнении команды.
     * Колбэк следует передавать на той стороне, которая посылала команду,
     * и должна обработать результат выполнения команды.
     *
     * @param callable $callback
     * @return $this
     */
    final public function onSuccess(callable $callback): self
    {
        $this->onSuccess = $callback;

        return $this;
    }

    /**
     * Задает колбэк, вызывающийся при неудачном выполнении команды.
     * Колбэк следует передавать на той стороне, которая посылала команду,
     * и должна обработать результат выполнения команды.
     *
     * @param callable $callback
     * @return $this
     */

    final public function onError(callable $callback): self
    {
        $this->onError = $callback;

        return $this;
    }

    abstract public function toArray(): array;

    abstract public static function fromArray(array $data, Client $client);
}
