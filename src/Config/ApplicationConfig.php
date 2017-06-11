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

    public function isApplicationClassValid(string $class): bool
    {
        return class_exists($class) && is_subclass_of($class, ApplicationInterface::class);
    }

    public function isApplicationExists(string $applicationId): bool
    {
        return isset($this->applications[$applicationId]);
    }

    public function getApplicationIdByClass(string $class): string
    {
        foreach ($this->applications as $appId => $appConfig) {
            if ($appConfig['class'] === $class) {
                return $appId;
            }
        }
        throw new \RuntimeException('Application id not found.', Saw::ERROR_APPLICATION_CLASS_NOT_EXISTS);
    }

    public function getApplicationClassById(string $id): string
    {
        return $this->applications[$id]['class'];
    }

    public function getApplicationArguments(string $applicationId): array
    {
        if (!isset($this->applications[$applicationId])) {
            throw new \UnexpectedValueException('Unexpected application id: ' . $applicationId);
        }
        return $this->applications[$applicationId]['arguments'] ?? [];
    }
}
