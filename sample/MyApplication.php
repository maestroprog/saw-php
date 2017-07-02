<?php

namespace {

    use Saw\Application\BasicMultiThreaded;
    use Saw\Saw;
    use Saw\Thread\AbstractThread;

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

        public function init()
        {

        }

        protected function main()
        {
            $header = microtime(true);
            $this->header = $this->thread('FOR1', function () {
                for ($i = 0; $i < 1000000; $i++) {
                    ;
                }
                return 1;
            });
            $header = ($article = microtime(true)) - $header;
            $this->article = $this->thread('FOR2', function () {
                for ($i = 0; $i < 1000000; $i++) {
                    ;
                }
                return 2;
            });
            $article = ($footer = microtime(true)) - $article;
            $this->footer = $this->thread('FOR3', function () {
                for ($i = 0; $i < 1000000; $i++) {
                    ;
                }
                return 3;
            });
            $footer = microtime(true) - $footer;
            $this->view = new DemoView('<h1>header</h1><p>article</p><h6>footer</h6>');
            $this->times = $this->threadArguments(
                'TIMESTAMPS',
                function (float $header, float $article, float $footer) {
                    return $this->view->build(compact('header', 'article', 'footer'));
                },
                [$header, $article, $footer]
            );
        }

        public function end()
        {
//            $this->synchronizeAll();
            var_dump('ended');
            echo $this->header->getResult(),
            $this->article->getResult(),
            $this->footer->getResult(),
            $this->times->getResult();
        }
    }

    class DemoView
    {
        private $template;

        public function __construct(string $template)
        {
            $this->template = $template;
        }

        public function build(array $variables): string
        {
            return Saw::getCurrentApp()->getId() . ' : '
                . str_replace(array_keys($variables), $variables, $this->template);
        }
    }
}
