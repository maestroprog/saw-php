<?php

namespace Maestroprog\Saw\Memory;

/**
 * Интерфейс расшариваемой памяти.
 * Расшариваемая память может находиться только на одном воркере,
 * но имеется возможность перекинуть часть памяти на другой воркер,
 * или запросить какую-то часть памяти одним воркером у другого воркера.
 *
 * Описывает возможности расшариваемой памяти.
 */
interface ShareableMemoryInterface extends MemoryInterface
{
    public function share(string $varName);

    public function request(string $varName);
}
