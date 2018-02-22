<?php

namespace Maestroprog\Saw;

function iterateGenerator(\Generator $generator)
{
    foreach ($generator as $g) {
        unset($g);
        continue;
    }

    return $generator->getReturn();
}
