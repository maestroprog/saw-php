<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Config\DaemonConfig;
use Maestroprog\Saw\ValueObject\ProcessStatus;

/**
 * Сервис, организующий запуск воркера.
 */
class WorkerStarter
{
    private $executor;
    private $cmd;

    public function __construct(Executor $executor, DaemonConfig $config)
    {
        $this->executor = $executor;

        if ($config->hasWorkerPath()) {
            $this->cmd = $config->getWorkerPath() . ' ' . $config->getConfigPath();
        } else {
            throw new \LogicException('Auto-configuration of the worker path is not supported.');
            /*$this->cmd = <<<CMD
-r "require_once '{$config->getInitScriptPath()}';
\Maestroprog\Saw\Saw::instance()->init('{$config->getConfigPath()}')->instanceWorker()->start();"
CMD;*/
        }
    }

    /**
     * Запускает воркер.
     * Вернёт объект @see ProcessStatus.
     *
     * @return ProcessStatus
     */
    public function start(): ProcessStatus
    {
        return $this->executor->exec($this->cmd);
    }
}
