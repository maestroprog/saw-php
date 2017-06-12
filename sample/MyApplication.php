<?php

namespace {

    use Saw\Application\BasicMultiThreaded;
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

        public function init()
        {
            // TODO: Implement init() method.
        }

        protected function main()
        {
            $this->header = $this->thread('FOR1', function () {
                for ($i = 0; $i < 100000; $i++) {
                    ;
                }
                return 1;
            });
            $this->article = $this->thread('FOR2', function () {
                for ($i = 0; $i < 100000; $i++) {
                    ;
                }
                return 2;
            });
            $this->footer = $this->thread('FOR3', function () {
                for ($i = 0; $i < 100000; $i++) {
                    ;
                }
                return 3;
            });
        }

        public function end()
        {
            $this->synchronizeAll();
            echo $this->header->getResult(), $this->article->getResult(), $this->footer->getResult();
        }
    }
}
