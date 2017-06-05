<?php

namespace Saw;

use Saw\Application\ApplicationInterface;
use Saw\Application\Basic;
use Saw\Heading\Singleton;

/**
 * Класс-синглтон, реализующий загрузку фреймворка Saw.
 */
final class Saw extends Singleton
{
    const ERROR_CLASS_NOT_EXISTS = 1;
    const ERROR_WRONG_CLASS = 2;

    public function init(array $config): self
    {
        return $this;
    }

    public function getApp(string $applicationId): ApplicationInterface
    {
        if (!class_exists($applicationClass)) {
            throw new \Exception(
                sprintf('ApplicationContainer class "%s" is missing.', $applicationClass),
                self::ERROR_CLASS_NOT_EXISTS
            );
        }
        if (!$applicationClass instanceof Basic) {
            throw new \Exception(
                sprintf('ApplicationContainer class "%s" is wrong.', $applicationClass),
                self::ERROR_WRONG_CLASS
            );
        }
    }

    public function createApp(string $applicationClass): ApplicationInterface
    {
        if (!class_exists($applicationClass)) {

        }
    }

    public function runApp(ApplicationInterface $application): ApplicationInterface
    {
        $application->run();
    }
}
