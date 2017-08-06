<?php

namespace Maestroprog\Saw\Command;

trait CommandCode
{
    /**
     * @var int
     */
    private $code;
    private $onSuccess;
    private $onError;

    /**
     * Метод вызывается диспетчером команд,
     * он реализует механизм вызова колбэков,
     * назначаемых в @see CommandCode::onSuccess()
     * и @see CommandCode::onError().
     *
     * @param $result array
     * @throws \RuntimeException Исключение бросается в случае получения неизвестного статуса выполнения команды
     */
    public function dispatch(&$result)
    {
        if ($this->isSuccess()) {
            if (is_callable($this->onSuccess)) {
                call_user_func($this->onSuccess, $this, $result);
            }
        } elseif ($this->isError()) {
            if (is_callable($this->onError)) {
                call_user_func($this->onError, $this, $result);
            }
        } else {
            throw new \RuntimeException('Why is not success and is not error?');
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->code === AbstractCommand::RES_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->code === AbstractCommand::RES_ERROR;
    }

    /**
     * Задает колбэк, вызывающийся при успешном выполнении команды.
     * Колбэк следует передавать на той стороне, которая посылала команду,
     * и должна обработать результат выполнения команды.
     *
     * @param callable $callback
     * @return $this
     */
    final public function onSuccess(callable $callback)
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

    final public function onError(callable $callback)
    {
        $this->onError = $callback;
        return $this;
    }

    /**
     * Сообщает об успешном выполнении команды.
     * Вызывается на той стороне, которая должна обработать поступившую команду.
     *
     * @throws \Exception
     */
    final public function success()
    {
        $this->code = self::RES_SUCCESS;
        $this->result(); // отправляем результаты работы
    }

    /**
     * Сообщает об ошибке выполнении команды.
     * Вызывается на той стороне, которая должна обработать поступившую команду.
     *
     * @throws \Exception
     */
    final public function error()
    {
        $this->code = self::RES_ERROR;
        $this->result(); // отправляем результаты работы
    }
}
