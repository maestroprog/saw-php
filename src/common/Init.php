<?php
/**
 * Net Server code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:17
 */

namespace Saw;

/**
 * Class Init
 * @package Saw
 */
trait Init
{
    private static $php_binary_path = 'php';

    public static function init(&$config)
    {
        if (self::class instanceof InitBased) {
            self::pre_init();
            foreach ($config as $category => &$values) {
                foreach ($values as $key => $var) {
                    if (isset(self::$$key))
                        self::$$key = $var;
                }
                unset($var, $key, $values);
            }
            unset($category);
            self::post_init();
            return true;
        }
        return false;
    }
}

/**
 * Interface InitBased
 * @package Saw
 */
interface InitBased
{
    public static function pre_init();

    public static function post_init();
}