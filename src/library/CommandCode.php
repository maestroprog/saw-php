<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 02.12.2016
 * Time: 0:51
 */

namespace maestroprog\saw\library;


trait CommandCode
{
    /**
     * @var int
     */
    private $code;

    private $onSuccess;
    private $onError;

    /**
     * Запускает механизм... todo результат выполнения команды
     */
    public function dispatch()
    {
        if ($this->isSuccess()) {
            if (is_callable($this->onSuccess)) {
                call_user_func($this->onSuccess);
            }
        } elseif ($this->isError()) {
            if (is_callable($this->onError)) {
                call_user_func($this->onError);
            }
        } else {
            throw new \Exception('Why is not success and is not error?');
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->code === Command::RES_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->code === Command::RES_ERROR;
    }

    /**
     * Задает колбэк, вызывающийся при успешном выполнении команды.
     *
     * @param callable $callback
     * @return $this
     */
    final public function setSuccess(callable $callback)
    {
        $this->onSuccess = $callback;
        return $this;
    }

    /**
     * Задает колбэк, вызывающийся при неудачном выполнении команды.
     *
     * @param callable $callback
     * @return $this
     */

    final public function setError(callable $callback)
    {
        $this->onError = $callback;
        return $this;
    }

    /**
     * Сообщает об успешном выполнении команды.
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
     *
     * @throws \Exception
     */
    final public function error()
    {
        $this->code = self::RES_ERROR;
        $this->result(); // отправляем результаты работы
    }
}
