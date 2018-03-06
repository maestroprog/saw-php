<?php

namespace Maestroprog\Saw\Helper;

final class Debug
{
    private static $debug;

    public static function enable(): void
    {
        if (!self::$debug) {
            self::$debug = true;

            /*set_exception_handler(function (\Throwable $exception) {
                if ($exception instanceof \Exception) {
                    echo $exception->getMessage();
                    exit(127);
                }
            });*/
        }
    }

    public static function is(): bool
    {
        return (bool)self::$debug;
    }
}
