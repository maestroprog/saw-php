<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 10.12.2016
 * Time: 14:52
 */

namespace maestroprog\library\worker;

use maestroprog\esockets\TcpClient;
use maestroprog\saw\library\Application;

class Core
{
    private $peer;

    public function __construct(
        TcpClient $peer,
        string $workerApp,
        string $workerAppClass
    )
    {
        $this->peer = $peer;
        if (empty($workerApp) || !file_exists($workerApp)) {
            trigger_error('Worker application configuration not found', E_USER_ERROR);
        }
        require_once $workerApp;
        if (!class_exists($workerAppClass)) {
            trigger_error('Worker application must be configured with "worker_app_class"', E_USER_ERROR);
        }
        $this->app = new $workerAppClass();
        if (!$this->app instanceof Application) {
            trigger_error('Worker application must be instance of maestroprog\saw\Application', E_USER_ERROR);
        }
    }

    /**
     * @var array
     */
    private $knowCommands = [];

    /**
     * Оповещает контроллер о том, что данный воркер узнал новую задачу.
     * Контроллер запоминает это.
     *
     * @param callable $callback
     * @param string $name
     * @param $result
     */
    public function addTask(callable &$callback, string $name, &$result)
    {
        if (!isset($this->knowCommands[$name])) {
            $this->knowCommands[$name] = [$callback, &$result];
            $this->sc->send([
                'command' => 'tadd',
                'name' => $name,
            ]);
        }
    }
}