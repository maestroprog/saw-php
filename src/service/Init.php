<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\saw\service;

use maestroprog\saw\command\TaskRes;
use maestroprog\saw\command\TaskRun;
use maestroprog\saw\entity\Command;
use maestroprog\saw\entity\Task;
use maestroprog\saw\library\Executor;
use maestroprog\esockets\debug\Log;
use maestroprog\saw\library\Factory;
use maestroprog\saw\library\TaskManager;
use maestroprog\saw\library\worker\Core;

/**
 * Воркер, использующийся входным скриптом.
 */
final class Init extends Worker
{
    use Executor;

    public $controller_path = 'controller.php';

    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * @throws \Exception
     */
    public function start()
    {
        $this->dispatcher = Factory::getInstance()->createDispatcher([
            new Command(TaskRun::NAME, TaskRun::class),
            new Command(
                TaskRes::NAME,
                TaskRes::class,
                function (TaskRes $context) {
                    try {
                        $task = $this->taskManager->getRunTask($context->getRunId());
                        $task->setResult($context->getResult());
                    } catch (\Throwable $e) {
                        $this->stop();
                        throw $e;
                    }
                }
            ),
        ]);
    }

    public function run()
    {
        $this->app = new $this->worker_app_class($this->taskManager);
        $this->app->run();
        $this->app->end();
        $this->stop();
    }

    public function addTask(Task $task)
    {
        $this->dispatcher->create(TaskRun::NAME, $this->sc)
            ->onError(function () use ($task) {
                //todo
                $task->setResult($this->taskManager->runCallback($task->getName()));
            })
            ->run(TaskRun::serializeTask($task));
    }

    final public function setTask(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
        return $this;
    }

    /**
     * @param array $config
     * @return Init|Worker
     * @throws \Exception
     */
    public static function create(array $config): Worker
    {
        $init = self::getInstance();
        if (!$init->init($config)) {
            throw new \Exception('Cannot initialize Init worker');
        }
        Log::log('configured. input...');
        try {
            if (!$init->connect()) {
                // controller autostart util
                Log::log('controller starting');
                $before_run = microtime(true);
                $init->exec($init->controller_path);
                Log::log('started');
                $after_run = microtime(true);
                usleep(10000); // await for run controller Saw
                $try = 0;
                while (true) {
                    $try_run = microtime(true);
                    if ($init->connect()) {
                        Log::log(sprintf(
                            'run: %f, exec: %f, connected: %f',
                            $before_run,
                            $after_run - $before_run,
                            $try_run - $after_run
                        ));
                        Log::log('before run time: ' . $before_run);
                        break;
                    }
                    if ($try++ > 10) {
                        throw new \Exception('Attempts were unsuccessfully');
                    }
                    usleep(10000);
                }
            }
            $init->start();
            Log::log('Init started');
            register_shutdown_function(function () use ($init) {
                Log::log('work start');
                //$init->work();
                Log::log('work end');

                //$init->stop();
                Log::log('closed');
            });
            return $init->setTask(Factory::getInstance()->createTaskManager($init));
        } catch (\Exception $e) {
            Log::log(sprintf('Saw connect or start failed with error: %s', $e->getMessage()));
            throw new \Exception('Framework starting fail', 0, $e);
        }
    }
}
