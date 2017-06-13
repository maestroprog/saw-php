<?php

namespace Saw\Service;

use Saw\Application\ApplicationInterface;
use Saw\Config\ApplicationConfig;
use Saw\SawFactory;

/**
 * Загрузчик приложений.
 * Используется для создания инстансов любых классов приложений, реализующих @see ApplicationInterface
 */
final class ApplicationLoader
{
    private $applicationConfig;
    private $factory;

    public function __construct(ApplicationConfig $config, SawFactory $factory)
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
        $arguments = $this->factory->instanceArguments($arguments);
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
        $arguments = $this->factory->instanceArguments($arguments);
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
            $this->instanceAppById($id);
        }, $allAppIds);
    }
}
