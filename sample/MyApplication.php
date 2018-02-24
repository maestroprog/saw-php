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
     * @var DemoView
     */
    private $view;

    /**
     * @var AbstractThread
     */
    private $times;

    private $t;

    public function init()
    {
        $this->t = microtime(true);
    }

    public function prepare()
    {
        return null;
    }

    protected function main($prepared)
    {
        $header = microtime(true);
        $this->header = $this->thread('FOR1', function () {
            $t1 = $this->thread('SUB_THREAD_1', function () {
                for ($i = 0; $i < 3; $i++) {
                    $this->time('WORK SUB_THREAD_1');
                    yield;
                }

                return 1;
            });
            $t2 = $this->thread('SUB_THREAD_2', function () {
                for ($i = 0; $i < 5; $i++) {
                    $this->time('WORK SUB_THREAD_2');
                    yield;
                }

                return 2;
            });

            $this->time('WORK FOR1');

            if (!$this->runThreads($t1, $t2)) {
                throw new \RuntimeException('Cannot run threads.');
            }

            $this->time('RUN FOR1');

            yield from $this->synchronizeThreads($t1, $t2);

            $this->time('SYNC FOR1');

            return $t1->getResult() + $t2->getResult();
        });
        $header = ($article = microtime(true)) - $header;
        $this->article = $this->thread('FOR2', function () {
            for ($i = 0; $i < 4; $i++) {
                $this->time('WORK FOR2');
                yield;
            }
            return 4;
        });
        $article = ($footer = microtime(true)) - $article;
        $this->footer = $this->thread('FOR3', function () {
            for ($i = 0; $i < 10; $i++) {
                $this->time('WORK FOR3');
                yield;
            }
//            $for3 = $this->context()->read('FOR3');
//            $for3++;
//            $this->context()->write('FOR3', $for3);
//            return '3-' . $for3;
            return 5;
        });
        $footer = microtime(true) - $footer;
        $this->view = new DemoView('<h1>header</h1><p>article</p><h6>footer</h6>');
        $this->times = $this->threadArguments(
            'TIMESTAMPS',
            function (float $header, float $article, float $footer) {
                $this->time('TIMESTAMPS');

                return $this->view->build(compact('header', 'article', 'footer'));
            },
            [$header, $article, $footer]
        );
    }

    public function end()
    {
        $time = microtime(true);
        iterateGenerator($this->synchronizeAll());
        var_dump('ended', microtime(true) - $time);
        var_dump(microtime(true) - $this->t);
        echo $this->header->getResult(),
        $this->article->getResult(),
        $this->footer->getResult(),
        $this->times->getResult();
    }

    private function time(string $log): void
    {
        echo sprintf('%f %s<br>' . PHP_EOL, microtime(true), $log);
    }
}
