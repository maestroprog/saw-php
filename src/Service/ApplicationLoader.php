<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Application\ApplicationInterface;
use Maestroprog\Saw\Config\ApplicationConfig;
use Maestroprog\Saw\Saw;
use Maestroprog\Saw\SawFactory;

/**
 * Загрузчик приложений.
 * Используется для создания инстансов любых классов приложений, реализующих @see ApplicationInterface
 */
final class ApplicationLoader
{
    private $applicationConfig;
    private $factory;

    public function __construct(ApplicationConfig $config, Saw $factory)
    {
        $this->applicationConfig = $config;
        $this->factory = $factory;
    }

    /**
     * Инстанцирует новый объект приложения.
     * Используется скриптом index.php.
     *
     * @param string $applicationClass
     * @return ApplicationInterface
     */
    public function instanceApp(string $applicationClass): ApplicationInterface
    {
        if (!$this->applicationConfig->isApplicationClassValid($applicationClass)) {
            throw new \RuntimeException('Invalid application class: ' . $applicationClass);
        }
        try {
            $arguments = $this->applicationConfig->getApplicationArguments(
                $this->applicationConfig->getApplicationIdByClass($applicationClass)
            );
        } catch (\RuntimeException $e) {
            $arguments = [];
        }

        $class = new \ReflectionClass($applicationClass);
        $appId = $this->applicationConfig->getApplicationIdByClass($applicationClass);
        $arguments = $this->factory->instanceArguments($arguments, ['appId' => $appId]);
        return $class->newInstanceArgs($arguments);
    }

    public function instanceAppById(string $applicationId): ApplicationInterface
    {
        if (!$this->applicationConfig->isApplicationExists($applicationId)) {
            throw new \RuntimeException('Invalid application id: ' . $applicationId);
        }
        try {
            $arguments = $this->applicationConfig->getApplicationArguments($applicationId);
        } catch (\RuntimeException $e) {
            $arguments = [];
        }

        $class = new \ReflectionClass($this->applicationConfig->getApplicationClassById($applicationId));
        $arguments = $this->factory->instanceArguments($arguments, ['appId' => $applicationId]);
        return $class->newInstanceArgs($arguments);
    }

    /**
     * Создаёт инстансы для всех приложений, описанных в разделе "applications" конфига.
     *
     * @return ApplicationInterface[]
     */
    public function instanceAllApps(): array
    {
        $allAppIds = $this->applicationConfig->getAllApplicationIds();

        return array_map(function (string $id) {
            return $this->instanceAppById($id);
        }, $allAppIds);
    }
}
