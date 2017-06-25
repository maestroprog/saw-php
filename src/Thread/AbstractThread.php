<?php

namespace Saw\Thread;

abstract class AbstractThread
{
    const STATE_NEW = 0; // поток создан
    const STATE_RUN = 1; // поток выполняется
    const STATE_END = 2; // выполнение потока завершено
    const STATE_ERR = 3; // ошибка при выполнении потока

    private $id;

    /**
     * @var string Уникальный идентификатор кода.
     */
    private $uniqueId;

    /**
     * @var array Аргументы, передаваемые коду (функции).
     */
    protected $arguments = [];

    private $state = self::STATE_NEW;
    private $result;

    public function __construct(int $id, string $uniqueId)
    {
        $this->id = $id;
        $this->uniqueId = $uniqueId;
    }

    /**
     * Вернёт числовой идентификатор.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Вернёт уникальный идентификатор потока.
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * Назначает аргументы для функции, в которой выполняется код потока.
     *
     * @param array $arguments
     * @return self
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Общее - тред можно:
     * 1. Создать
     * 2. Задать список аргументов
     * 3. Запустить
     * 4. Получить состояние
     * 5. Получить айдишник потока
     * 6. получить результат работы
     * 7. Остановить выполнение потока (актуально как для мультиплексирования, так и для обычного режима).
     *    В виду имеется обычный рисет. то есть, полная остановка работы потока.
     * ...
     *
     * Внутри треда можно:
     * 1. Слипнуть поток на какое то время (в буквальном смысле).
     *
     * Мультиплексирование:
     * 1. Задать операционный размер шага (кол-во операций выполняемых за 1 раз)
     * 2. Задать общий операционный размер (общее кол-во операций которое необходимо выполнить)
     * 3. Получить процент выполнения исходя из текущего шага. При неопределенном состоянии вернет -1.
     * 4. Задать приоритет по занимаемому времени выполнения в процентном соотношении
     * 5. Переключиться на поток.
     * 5.1. Назначить колбек-который вызовется при переключении на поток.
     * 6. Отключиться от потока.
     * 6.1 Назначить колбек-который вызовется при переключении с потока на другой поток.
     *
     */

    /**
     * Выполняет код потока.
     * @todo тут должны быть какие-нибудь исключения.
     * @todo кстати, это абстрактный метод!
     *
     * @param void
     * @return void
     */
    abstract public function run();

    public function getCurrentState(): int
    {
        return $this->state;
    }

    public function hasResult(): bool
    {
        return is_null($this->result);
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult($data)
    {
        $this->state = self::STATE_END;
        $this->result = $data;
    }
}
