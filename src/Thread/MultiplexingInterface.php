<?php

namespace Maestroprog\Saw\Thread;

/**
 * Интерфейс поддержки мультиплексирования.
 * Thread-ы его должны имплементировать.
 */
interface MultiplexingInterface
{
    /**
     * Назначает операционный размер шага для мультиплексируемого потока.
     *
     * @param int $stepSize
     * @return mixed
     */
    public function setOperationStepSize(int $stepSize);

    /**
     * Назначает общий операционный размер потока.
     *
     * @param int $size
     * @return mixed
     */
    public function setOperationSize(int $size);

    /**
     * Возвращает приоритет потока при мультиплексировании,
     * по отношению к другим потокам, в процентах от 0 до 100.
     *
     * @return int
     */
    public function getMuxPriority(): int;

    /**
     * Ставит поток в состояние паузы, то есть этот метод
     * вызывается, когда система временно переключается с данного
     * потока на другой поток.
     *
     * @return mixed
     */
    public function switchOff();

    /**
     * Назначает коллбек, который выполнится при переключении с потока.
     * Это что-то вроде события, и оно может пригодится в некоторых случаях,
     * например - когда требуется освободить память.
     *
     * @param callable $callback
     * @return mixed
     */
    public function onSwitchOff(callable $callback);

    /**
     * Аналогично методу @see switchOff, только наоборот, этот метод
     * вызывается при переключении на данный поток.
     *
     * @return mixed
     */
    public function switchOn();

    /**
     * Назначает коллбек, который выполнится при переключении на поток.
     * Аналогично методу @see onSwitchOff.
     *
     * @param callable $callback
     * @return mixed
     */
    public function onSwitch(callable $callback);
}
