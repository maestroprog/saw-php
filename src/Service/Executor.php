<?php

namespace Saw\Service;

use SebastianBergmann\GlobalState\RuntimeException;

final class Executor
{
    /**
     * @var string path to php binaries
     */
    public $phpBinaryPath = 'php';

    public function __construct(string $phpBinaryPath = null)
    {
        if (!is_null($phpBinaryPath)) {
            $this->phpBinaryPath = $phpBinaryPath;
        }
    }

    /**
     * Выполняет команду, и возвращает ID запущенного процесса.
     *
     * @param $cmd
     * @return int process id (pid)
     */
    public function exec($cmd): int
    {
        $cmd = sprintf('%s -f %s', $this->phpBinaryPath, $cmd);
        if (PHP_OS === 'WINNT') {
            $cmd = str_replace('\\', '\\\\', $cmd);
        }
        $pipes = [STDIN, STDOUT, STDERR];
        $resource = proc_open($cmd, [], $pipes, null, null, null);
        if (false === $resource) {
            throw new \RuntimeException('Cannot be run ' . $cmd);
        }
        $status = proc_get_status($resource);
        if (!isset($status['pid'])) {
            throw new RuntimeException('Cannot get pid of running ' . $cmd);
        }
        return $status['pid'];
    }

    /**
     * Прихлопывает запущенный процесс.
     * @param resource $pid
     */
    public function kill($pid)
    {
        if (!is_resource($pid)) {
            throw new \InvalidArgumentException('Pid is not resource.');
        }
        proc_close($pid);
    }
}
