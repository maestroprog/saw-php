<?php

namespace Maestroprog\Saw\Heading;

/**
 * Интерфейс для поддержки отчётов о работе для компонентов.
 */
interface ReportSupportInterface
{
    /**
     * Возвращает массив рабочих параметров.
     * По замыслу должен вернуть подробный список параметров.
     *
     * @return array
     */
    public function getFullReport(): array;
}
