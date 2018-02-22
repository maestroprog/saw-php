<?php

namespace Maestroprog\Saw\Service;

use Esockets\debug\Log;
use Maestroprog\Saw\ValueObject\ProcessStatus;

final class Executor
{
    /**
     * @var string path to php binaries
     */
    public $phpBinaryPath = '/usr/bin/php';

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
     * @return ProcessStatus
     */
    public function exec($cmd): ProcessStatus
    {
        $cmd = sprintf('%s %s', $this->phpBinaryPath, $cmd . ' &');
        if (PHP_OS === 'WINNT') {
            $cmd = str_replace('/', '\\', $cmd);
            $cmd = str_replace('\\', '\\\\', $cmd);
        }
        if (PHP_SAPI !== 'cli') {
//            define('STDIN', fopen('php://stdin', 'r'));
            define('STDOUT', fopen('php://stdout', 'w'));
            define('STDERR', fopen('php://stderr', 'w'));
        }
        $pipes = [['pipe', 'r'], STDOUT, STDERR];
        $pipesOpened = [];
        Log::log($cmd);
        $resource = proc_open($cmd, $pipes, $pipesOpened, null, null, ['bypass_shell' => true]);
        if (false === $resource) {
            throw new \RuntimeException('Cannot be run ' . $cmd);
        }
        return new ProcessStatus($resource, $pipesOpened);
    }

    /**
     * Прихлопывает запущенный процесс.
     * @param ProcessStatus $processStatus
     */
    public function kill(ProcessStatus $processStatus)
    {
        proc_close($processStatus->getResource());
    }
}
