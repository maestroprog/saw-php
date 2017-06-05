<?php

namespace Saw\Service;

final class Executor
{
    /**
     * @var string path to php binaries
     */
    public $phpBinaryPath = 'php';

    public function __construct(string $phpBinaryPath)
    {
        $this->phpBinaryPath = $phpBinaryPath;
    }

    public function exec($cmd)
    {
        $cmd = sprintf('%s -f %s', $this->phpBinaryPath, $cmd);
        if (PHP_OS === 'WINNT') {
            $cmd = str_replace('\\', '\\\\', $cmd);
        }
        $pipes = [STDIN, STDOUT, STDERR];
        $pid = proc_open($cmd, [], $pipes, null, null, null);
        if (false === $pid) {
            throw new \RuntimeException('Cannot be run ' . $cmd);
        }
        return $pid;
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
