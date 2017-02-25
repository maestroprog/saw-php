<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 25.02.2017
 * Time: 18:04
 */

namespace maestroprog\saw;

use maestroprog\saw\Application\Application;
use maestroprog\saw\Application\Basic;
use maestroprog\saw\Heading\Singleton;

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

    public function getApp(string $applicationId): Application
    {
        if (!class_exists($applicationClass)) {
            throw new \Exception(
                sprintf('Application class "%s" is missing.', $applicationClass),
                self::ERROR_CLASS_NOT_EXISTS
            );
        }
        if (!$applicationClass instanceof Basic) {
            throw new \Exception(
                sprintf('Application class "%s" is wrong.', $applicationClass),
                self::ERROR_WRONG_CLASS
            );
        }
    }

    public function runApp(Application $application): Application
    {
        $application->run();
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}