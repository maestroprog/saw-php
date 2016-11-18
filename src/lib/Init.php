<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 13.10.2016
 * Time: 22:48
 */

namespace maestroprog\Saw;

/**
 * Воркер, использующийся входным скриптом.
 */
class Init extends Worker
{
    protected static $instance;

    public function start()
    {
        out('starting');
        $before_run = microtime(true);
        $this->exec($this->controller_path . DIRECTORY_SEPARATOR . 'controller.php');
        out('started');
        $after_run = microtime(true);
        usleep(10000); // await for run controller Saw
        $try = 0;
        do {
            $try_run = microtime(true);
            #usleep(100000);
            if ($this->connect()) {
                out(sprintf('run: %f, exec: %f, connected: %f', $before_run, $after_run - $before_run, $try_run - $after_run));
                out('before run time: ' . $before_run);
                return true;
            }
            usleep(10000);
        } while ($try++ < 10);
        return false;
    }


    private function exec($cmd)
    {
        $cmd = sprintf('%s -f %s', $this->php_binary_path, $cmd);
        if (PHP_OS === "WINNT") {
            $cmd = str_replace('\\', '\\\\', $cmd);
            pclose(popen($e = "start " . $cmd, "r"));
        } else {
            exec($e = $cmd . " > /dev/null 2>&1 &");
        }
        out($e);
    }

    private function kill()
    {
        // todo
    }
}
