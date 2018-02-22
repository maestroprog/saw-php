<?php

namespace Maestroprog\Saw\Service;

use Qwerty\Application\ApplicationFactory;
use Qwerty\Application\ApplicationInterface;
use Maestroprog\Saw\Config\ApplicationConfig;

/**
 * Загрузчик приложений.
 * Используется для создания инстансов любых классов приложений, реализующих @see ApplicationInterface
 */
final class ApplicationLoader
{
    private $applicationConfig;
    private $factory;

    public function __construct(ApplicationConfig $config, ApplicationFactory $factory)
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

        $appId = $this->applicationConfig->getApplicationIdByClass($applicationClass);

        return $this->instanceAppWith(
            $applicationClass,
            $arguments,
            ['appId' => $appId]
        );
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

        return $this->instanceAppWith(
            $this->applicationConfig->getApplicationClassById($applicationId),
            $arguments,
            ['appId' => $applicationId]
        );
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

    private function instanceAppWith(string $class, array $arguments, array $variables = []): ApplicationInterface
    {
        $class = new \ReflectionClass($class);

        $arguments = $this->factory->instanceArguments($arguments, $variables);
        /** @var ApplicationInterface $app */
        $app = $class->newInstanceArgs($arguments);
        return $app;
    }
}
