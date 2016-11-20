<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\Saw;

use maestroprog\esockets\debug\Log;

/**
 * Воркер, использующийся входным скриптом.
 */
class Init extends Worker
{
    use Executer;

    protected static $instance;

    public function start()
    {
        Log::log('starting');
        $before_run = microtime(true);
        $this->exec($this->controller_path . DIRECTORY_SEPARATOR . 'controller.php');
        Log::log('started');
        $after_run = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        do {
            $try_run = microtime(true);
            #usleep(100000);
            if ($this->connect()) {
                Log::log(sprintf('run: %f, exec: %f, connected: %f', $before_run, $after_run - $before_run, $try_run - $after_run));
                Log::log('before run time: ' . $before_run);
                return true;
            }
            usleep(10000);
        } while ($try++ < 10);
        return false;
    }

    private function kill()
    {
        // todo
    }

    public function addTask(callable &$callback, string $name, &$result)
    {
        parent::addTask($callback, $name, $result);
        return $this->sc->send([
            'command' => 'trun',
            'name' => $name,
        ]);
    }
}
