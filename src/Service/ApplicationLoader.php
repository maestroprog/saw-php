<?php

namespace Saw\Service;


use Saw\Application\ApplicationInterface;
use Saw\Config\ApplicationConfig;
use Saw\Saw;
use Saw\SawFactory;

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
     * Используется воркером и скриптом index.php.
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
            var_dump($e->getMessage());
            $arguments = [];
        }

        $class = new \ReflectionClass($applicationClass);
        $arguments = $this->factory->instanceArguments($arguments);
        return $class->newInstanceArgs($arguments);
    }

    /**
     * Получает объект приложения по его ID.
     * Используется todo?
     *
     * @param string $applicationId
     * @return ApplicationInterface
     * @throws \Exception
     */
    public function getApp(string $applicationId): ApplicationInterface
    {
        if (!$this->applicationConfig->isApplicationExists($applicationId)) {
            throw new \RuntimeException(
                sprintf('Unknown application.', $applicationClass),
                Saw::ERROR_APPLICATION_CLASS_NOT_EXISTS
            );
        }
        $applicationClass = $this->applicationConfig->getApplicationClassById($applicationId);
    }
}
