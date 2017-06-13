<?php

namespace Saw\Config;

use Saw\Application\ApplicationInterface;
use Saw\Saw;

final class ApplicationConfig
{
    private $applications = [];

    public function __construct(array $config)
    {
        // config validation
        foreach ($config as $appId => $appConfig) {
            if (!isset($appConfig['class'])) {
                throw new \UnexpectedValueException('Unexpected application config, app id: ' . $appId);
            }
            if (!$this->isApplicationClassValid($appConfig['class'])) {
                throw new \InvalidArgumentException(
                    'Invalid application class: ' . $appConfig['class'],
                    Saw::ERROR_WRONG_APPLICATION_CLASS
                );
            }
            $this->applications[$appId] = $appConfig;
        }
    }

    /**
     * Проверяет, является ли указанный класс валидным классом приожения.
     *
     * @param string $class
     * @return bool
     */
    public function isApplicationClassValid(string $class): bool
    {
        return class_exists($class) && is_subclass_of($class, ApplicationInterface::class);
    }

    /**
     * Проверяет, является ли указанный id соответствующим приложени в конфиге.
     *
     * @param string $applicationId
     * @return bool
     */
    public function isApplicationExists(string $applicationId): bool
    {
        return isset($this->applications[$applicationId]);
    }

    /**
     * Вернёт id приложения по его классу.
     * Если одному классу приложения соответсвует несколько id,
     * то лучше этот метод не использовать.
     *
     * @param string $class
     * @return string
     */
    public function getApplicationIdByClass(string $class): string
    {
        foreach ($this->applications as $appId => $appConfig) {
            if ($appConfig['class'] === $class) {
                return $appId;
            }
        }
        throw new \RuntimeException('Application id not found.', Saw::ERROR_APPLICATION_CLASS_NOT_EXISTS);
    }

    /**
     * Вернёт класс приложения по его id.
     *
     * @param string $id
     * @return string
     */
    public function getApplicationClassById(string $id): string
    {
        return $this->applications[$id]['class'];
    }

    /**
     * Вернёт в виде массива список аргументов, необходимых для создания инстанса объекта приложения.
     *
     * @param string $applicationId
     * @return array
     */
    public function getApplicationArguments(string $applicationId): array
    {
        if (!isset($this->applications[$applicationId])) {
            throw new \UnexpectedValueException('Unexpected application id: ' . $applicationId);
        }
        return $this->applications[$applicationId]['arguments'] ?? [];
    }

    /**
     * Вернёт список всех id приложений, описанных в конфиге.
     *
     * @return string[]
     */
    public function getAllApplicationIds(): array
    {
        return array_keys($this->applications);
    }
}
