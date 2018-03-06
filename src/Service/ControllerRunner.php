<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\Config\DaemonConfig;
use Maestroprog\Saw\ValueObject\ProcessStatus;

class ControllerRunner
{
    private $executor;
    private $cmd;

    public function __construct(Executor $executor, DaemonConfig $config)
    {
        $this->executor = $executor;

        if ($config->hasControllerPath()) {
            $this->cmd = $config->getControllerPath() . ' ' . $config->getConfigPath();
        } else {
            throw new \LogicException('Auto-configuration of the controller path is not supported.');
            /*$this->cmd = <<<CMD
-r "require_once '{$config->getInitScriptPath()}';
\Maestroprog\Saw\Saw::instance()->init('{$config->getConfigPath()}')->instanceController()->start();"
CMD;*/
        }
    }

    /**
     * @throws \RuntimeException
     */
    public function start(): ProcessStatus
    {
        return $this->executor->exec($this->cmd);
    }
}
