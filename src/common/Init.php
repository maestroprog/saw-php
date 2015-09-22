<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 23.09.2015
 * Time: 0:17
 */

namespace Saw;


trait Init
{

    private static $php_binary_path = 'php';

    public static function configure(&$config)
    {
        foreach ($config as $category => &$values) {
            foreach ($values as $key => $var) {
                if (isset(self::$$key))
                    self::$$key = $var;
            }
            unset($var, $key, $values);
        }
        unset($category);
    }
}