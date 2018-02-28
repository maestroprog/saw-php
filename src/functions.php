<?php

namespace Maestroprog\Saw;

use Esockets\Debug\Log;

/**
 * @param \Generator $generator
 * @param int $timeout
 *
 * @return mixed|null
 * @throws \RuntimeException
 */
function iterateGenerator(\Generator $generator, int $timeout = 0)
{
    $time = time();
    $timeoutTime = $time + $timeout;

    foreach ($generator as $key => $value) {
        if (Saw::isDebugEnabled()) {
            Log::log($key, $value);
        }

        if ($timeout && time() >= $timeoutTime) {
            throw new \RuntimeException('Iteration timeout.');
        }
    }

    return $generator->getReturn();
}
