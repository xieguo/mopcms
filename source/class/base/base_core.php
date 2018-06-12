<?php
/**
 * 调用未定义属性或方法时的异常处理
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
abstract class base_core
{
    public function __construct() {}

    public function __set($name, $value)
    {
        throw new Exception('The property ' . $name . '" no exist in class ' . get_class($this));
    }

    public function __get($name)
    {
        throw new Exception('The property ' . $name . '" no exist in class ' . get_class($this));
    }

    public function __call($name, $parameters)
    {
        throw new Exception('The method ' . $name . ' no exist in class ' . get_class($this));
    }

    public static function __callStatic($name, $arguments)
    {
        throw new Exception('The method ' . $name . ' no exist in class ' . get_class($this));
    }

}