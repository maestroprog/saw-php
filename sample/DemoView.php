<?php

namespace Maestroprog\Saw\Sample;

use Maestroprog\Saw\Saw;

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
