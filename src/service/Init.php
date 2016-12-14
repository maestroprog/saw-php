<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\saw\service;

use maestroprog\saw\command\TaskRun;
use maestroprog\saw\entity\Command;
use maestroprog\saw\entity\Task;
use maestroprog\saw\library\Executor;
use maestroprog\esockets\debug\Log;
use maestroprog\saw\library\Factory;

/**
 * Воркер, использующийся входным скриптом.
 */
final class Init extends Worker
{
    use Executor;

    public $controller_path = 'controller.php';

    protected static $instance;

    /**
     * @throws \Exception
     */
    public function start()
    {
        Log::log('starting');
        $before_run = microtime(true);
        $this->exec($this->controller_path);
        Log::log('started');
        $after_run = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        do {
            $try_run = microtime(true);
            try {
                $this->connect();
                Log::log(sprintf(
                    'run: %f, exec: %f, connected: %f',
                    $before_run,
                    $after_run - $before_run,
                    $try_run - $after_run
                ));
                Log::log('before run time: ' . $before_run);

                $this->dispatcher = Factory::getInstance()->createDispatcher([
                    new Command(TaskRun::NAME, TaskRun::class),
                ]);
            } catch (\Exception $e) {
                usleep(10000);
            }
        } while ($try++ < 10);
        throw new \Exception('Attempts were unsuccessfully');
    }

    public function addTask(Task $task)
    {
        $this->dispatcher->create(TaskRun::NAME, $this->sc)
            ->setSuccess(function (&$result) use ($task) {
                $task->setResult($result);
            })
            ->setError(function () use ($task) {
                throw new \Exception('Failed executing Task ' . $task->getName());
            })
            ->run(TaskRun::serializeTask($task));
    }

    /**
     * @param array $config
     * @return Worker
     * @throws \Exception
     */
    public static function create(array $config): Worker
    {
        $init = Init::getInstance();
        if ($init->init($config)) {
            Log::log('configured. input...');
            try {
                $init->connect();
                $init->start();
            } catch (\Exception $e) {
                Log::log(sprintf('Saw connect or start failed with error: %s', $e->getMessage()));
                throw new \Exception('Framework starting fail');
            }
            register_shutdown_function(function () use ($init) {
                Log::log('work start');
                //$init->work();
                Log::log('work end');

                $init->stop();
                Log::log('closed');
            });
            return $init->setTask(Factory::getInstance()->createTaskManager($init));
        } else {
            throw new \Exception('Cannot initialize Init worker');
        }
    }
}
