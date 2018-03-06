<?php

namespace Maestroprog\Saw\Sample;

use Maestroprog\Saw\Application\BasicMultiThreaded;
use Maestroprog\Saw\Helper\Debug;
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

    protected function main($prepared = null): \Generator
    {
        $this->header = $this->thread('FOR1', function () {
            $this->time('WORK FOR1');

            $t1 = $this->thread('SUB_THREAD_1', function () {
                for ($i = 0; $i < 300; $i++) {
                    $this->time('WORK SUB_THREAD_1 ' . $i);
//                    yield;
                }
                $this->time('COMPLETE SUB_THREAD_1');

                $inner = $this->threadArguments('SUBSUBINNER1', function ($i): \Generator {
                    $this->time('WORK SUBSUBINNER1');
                    yield 1;
                    $this->time('WORK SUBSUBINNER1 1');
                    yield 2;
                    $this->time('WORK SUBSUBINNER1 2');
                    yield 3;
                    $this->time('WORK SUBSUBINNER1 3');
                    $this->time('COMPLETE SUBSUBINNER1');
                    return $i + 2;
                }, [$i]);

                $this->runThreads($inner);

                yield from $this->synchronizeThreads($inner);

                return $inner->getResult();
            });
            $t2 = $this->thread('SUB_THREAD_2', function () {
                for ($i = 0; $i < 500; $i++) {
                    $this->time('WORK SUB_THREAD_2 ' . $i);
//                    yield;
                }

                $addition = $this->threadArguments('ADDITION', function (int $i, int $j): \Generator {
                    $this->time('WORK ADDITION ' . $i);

                    yield;
                    $result = $i + $j;

                    $this->time('COMPLETE ADDITION ' . $i);

                    return $result;
                }, [$i, 2]);

                $this->runThreads($addition);

                yield from $this->synchronizeThreads($addition);

                $this->time('COMPLETE SUB_THREAD_2');

                return $addition->getResult();
            });

            if (!$this->runThreads($t1, $t2)) {
                throw new \RuntimeException('Cannot run threads.');
            }

            $this->time('RUN FOR1 SUBTHREADS');

            yield from $this->synchronizeThreads($t1, $t2);

            $this->time('COMPLETE SYNC FOR1');

            return $t1->getResult() + $t2->getResult();
        });
        $this->article = $this->thread('FOR2', function () {
            for ($i = 0; $i < 200; $i++) {
                $this->time('WORK FOR2 ' . $i);
//                yield;
            }
            $this->time('COMPLETE FOR2');
            return $i;
        });
        $this->footer = $this->thread('FOR3', function () {
            for ($i = 0; $i < 300; $i++) {
                $this->time('WORK FOR3 ' . $i);
//                yield;
            }
//            $for3 = $this->context()->read('FOR3');
//            $for3++;
//            $this->context()->write('FOR3', $for3);
//            return '3-' . $for3;
            $this->time('COMPLETE FOR3');
            return $i;
        });
        $this->end = $this->thread('FOR4', function () {
            for ($i = 0; $i < 200; $i++) {
                $this->time('WORK FOR4 ' . $i);
//                yield;
            }
            $this->time('COMPLETE FOR4');

            return $i;
        });

        if (!$this->runThreads($this->header, $this->article, $this->footer, $this->end)) {
            throw new \RuntimeException('Cannot run threads.');
        }
        yield from $this->synchronizeThreads($this->header, $this->article, $this->footer, $this->end);

        return $this->header->getResult()
            . $this->article->getResult()
            . $this->footer->getResult()
            . $this->end->getResult();
    }

    public function end()
    {
        Timings::start('MYAPP END');
        try {
            iterateGenerator($this->synchronizeAll(), 5);
        } catch (\RuntimeException $e) {
            echo $e->getMessage();
        }

        var_dump($this->mainThread->getResult());
        var_dump((microtime(true) - $this->t) * 1000, 'ms');
        Timings::clock();
    }

    private function time(string $log): void
    {
        if (Debug::is()) {
            echo sprintf('%f %s<br>' . PHP_EOL, microtime(true), $log);
        }
    }
}
