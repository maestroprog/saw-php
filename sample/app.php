<?php
use maestroprog\saw\Application\Basic;

/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 26.02.2017
 * Time: 2:51
 */

/**
 * @var $this Basic
 */

$this->thread('MODULE_1_INIT', function () {
    for ($i = 0; $i < 10000000; $i++) {
        'nope' ?: null;
    }
    return 'i';
});

$this->thread('MODULE_2_INIT', function () {
    for ($i = 0; $i < 10000000; $i++) {
        'nope' ?: null;
    }
    return 'i2';
});

$this->thread('MODULE_3_INIT', function () {
    for ($i = 0; $i < 10000000; $i++) {
        'nope' ?: null;
    }
    return 'i3';
});

$this->thread('MODULE_4_INIT', function () {
    for ($i = 0; $i < 10000000; $i++) {
        'nope' ?: null;
    }
    return 'i4';
});
