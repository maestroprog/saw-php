<?php

namespace Maestroprog\Saw\Sample;

class Timings
{
    private static $timings = [];

    private static $stack = [];

    private static $current;
    private static $time;

    public static function start(string $code): void
    {
        if (strpos(ENV, 'WORKER') === 0) {
            return;
        }
        if (isset(self::$stack[$code]) || $code === self::$current) {
            die('error');
        }
        if (self::$current) {
            self::$stack[self::$current] = self::$time;
        }

        self::$time = microtime(true);
        self::$current = $code;
    }

    public static function clock(string $code = null): void
    {
        if (strpos(ENV, 'WORKER') === 0) {
            return;
        }
        if ($code && $code !== self::$current) {
            if (!isset(self::$stack[$code])) {
                echo $code;
                exit;
            }
            $stack = self::$stack[$code];
            unset(self::$stack[$code]);
            self::$timings[] = [self::$current => microtime(true) - $stack];
        } else {
            $cur = self::$current;
            $time = self::$time;
            if (self::$stack) {
                self::$time = end(self::$stack);
                self::$current = key(self::$stack);
                unset(self::$stack[self::$current]);
            } else {
                self::$current = null;
            }
            self::$timings[] = [$cur => microtime(true) - $time];

        }
    }

    public static function dump(): void
    {
        if (self::$current) {
            self::$stack[self::$current] = self::$time;
        }
        foreach ((self::$stack) as $key => $time) {
            self::$timings[] = [$key => microtime(true) - $time];
        }
        ksort(self::$timings);
        $prev = null;
        $sum = 0;
        $c = 0;
        $cd = [];
        $cc = [];
        foreach (self::$timings as $items) {
            foreach ($items as $code => $time) {
                if (null === $prev || $code === $prev) {
                    $sum += $time;
                    $c++;
                } else {
//                    var_dump($prev . ' (' . $c . '): ' . ($sum * 1000));
//                    if ($c > 1) {
//                    }
//                    var_dump($code . '  : ' . ($sum * 1000));
                    $sum = $time;
                    $c = 1;
                }
                $cd[$code] = ($cd[$code] ?? 0) + $time;
                $cc[$code] = ($cc[$code] ?? 0) + 1;
                $prev = $code;
            }
        }
        var_dump($prev . ' (' . $c . '): ' . ($sum * 1000));
        var_dump('=======');
        foreach ($cd as $key => $time) {
            var_dump($key . ' (' . $cc[$key] . '): ' . ($time * 1000) . ' ' . number_format($time * 100 / $cd['INDEX.PHP'], 2) . '%');
        }
    }
}
