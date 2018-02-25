<?php

namespace Maestroprog\Saw\Sample;

use Maestroprog\Saw\Application\BasicMultiThreaded;
use Maestroprog\Saw\Thread\AbstractThread;
use function Maestroprog\Saw\iterateGenerator;

class MyApplication extends BasicMultiThreaded
{
    const ID = 'saw.sample.www';
    /**
     * @var AbstractThread
     */
    private $header;
    /**
     * @var AbstractThread
     */
    private $article;
    /**
     * @var AbstractThread
     */
    private $footer;
    /**
     * @var AbstractThread
     */
    private $end;

    private $t;

    public function init()
    {
        $this->t = microtime(true);
//        $this->view = new DemoView('<h1>header</h1><p>article</p><h6>footer</h6>');
    }

    public function prepare()
    {
        return null;
    }

    protected function main($prepared)
    {
        $this->header = $this->thread('FOR1', function () {
            $this->time('WORK FOR1');

            $t1 = $this->thread('SUB_THREAD_1', function () {
                for ($i = 0; $i < 3; $i++) {
                    $this->time('WORK SUB_THREAD_1 ' . $i);
                    yield;
                }
                $this->time('COMPLETE SUB_THREAD_1');

                return 1;
            });
            $t2 = $this->thread('SUB_THREAD_2', function () {
                for ($i = 0; $i < 5; $i++) {
                    $this->time('WORK SUB_THREAD_2 ' . $i);
                    yield;
                }
                $this->time('COMPLETE SUB_THREAD_2');

                return 2;
            });

            if (!$this->runThreads($t1, $t2)) {
                throw new \RuntimeException('Cannot run threads.');
            }

            $this->time('RUN FOR1 SUBTHREADS');

            yield from $this->synchronizeThreads($t1, $t2);

            $this->time('COMPLETE SYNC FOR1');
//
            return $t1->getResult() + $t2->getResult();
        });
        $this->article = $this->thread('FOR2', function () {
            for ($i = 0; $i < 2; $i++) {
                $this->time('WORK FOR2 ' . $i);
                yield;
            }
            $this->time('COMPLETE FOR2');
            return $i;
        });
        $this->footer = $this->thread('FOR3', function () {
            for ($i = 0; $i < 3; $i++) {
                $this->time('WORK FOR3 ' . $i);
                yield;
            }
//            $for3 = $this->context()->read('FOR3');
//            $for3++;
//            $this->context()->write('FOR3', $for3);
//            return '3-' . $for3;
            $this->time('COMPLETE FOR3');
            return $i;
        });
        $this->end = $this->thread('FOR4', function () {
            for ($i = 0; $i < 2; $i++) {
                $this->time('WORK FOR4 ' . $i);
                yield;
            }
            $this->time('COMPLETE FOR4');

            return $i;
        });
    }

    public function end()
    {
        try {
            iterateGenerator($this->synchronizeAll(), 5);
        } catch (\RuntimeException $e) {
            echo $e->getMessage();
        }
        echo $this->header->getResult(),
        $this->article->getResult(),
        $this->footer->getResult(),
        $this->end->getResult();
        var_dump((microtime(true) - $this->t) * 1000, 'ms');
    }

    private function time(string $log): void
    {
        echo sprintf('%f %s<br>' . PHP_EOL, microtime(true), $log);
    }
}
