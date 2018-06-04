<?php
namespace dxkite\support\proxy;

use dxkite\support\proxy\exception\ProxyException;
use ReflectionMethod;

class Proxy
{
    private $object;
    
    public function __construct($object)
    {
        $this->object=$object;
    }
    
    private static function call($object, string $method, array $param_arr=[])
    {
        if (visitor()->canAccess([$object,$method])) {
            $methodrefer=new ReflectionMethod($object, $method);
            return $methodrefer->invokeArgs($object, $param_arr);
        }
        throw new ProxyException(__('permission deny'));
    }

    public function __call(string $method, array $param_arr)
    {
        if (is_string($this->object) && preg_match('/^https?/',$this->object)) {
            $object=new \dxkite\support\api\Method($this->object);
            return $object->$method($param_arr);
        }
        return self::call($this->object, $method, $param_arr);
    }
}
