<?php

namespace Maestroprog\Saw\Memory;

/**
 * Интерфейс локализованной памяти.
 * Локализованная память находится только на одном воркере,
 * но имеется возможность перекинуть часть памяти на другой воркер,
 * или запросить какую-то часть памяти одним воркером у другого воркера.
 *
 * Описывает возможности локализованной памяти.
 */
interface LocalizedMemoryInterface extends MemoryInterface
{
    public function share(string $varName);

    public function request(string $varName);
}
