<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 21.11.2016
 * Time: 1:30
 */

namespace maestroprog\Saw;


use maestroprog\esockets\debug\Log;

trait Executor
{
    public $php_binary_path;

    private function exec($cmd)
    {
        $cmd = sprintf('%s -f %s', $this->php_binary_path, $cmd);
        if (PHP_OS === "WINNT") {
            $cmd = str_replace('\\', '\\\\', $cmd);
            pclose(popen($e = "start " . $cmd, "r"));
        } else {
            exec($e = $cmd . " > /dev/null 2>&1 &");
        }
        Log::log($e);
    }
}