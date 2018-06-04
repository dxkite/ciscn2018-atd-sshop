<?php
namespace dxkite\support\proxy;

use dxkite\support\visitor\Context;
use dxkite\support\visitor\exception\PermissionExcepiton;


abstract class ProxyObject
{
    public function getContext()
    {
        return Context::getInstance();
    }

    public static function getUserId()
    {
        $visitor=Context::getInstance()->getVisitor();
        if ($visitor->isGuest()) {
            throw new PermissionExcepiton(__('permission deny: unsupport guest to run this'), 1);
        }
        return $visitor->getId();
    }

    public static function hasPermission($p)
    {
        return  Context::getInstance()->getVisitor()->hasPermission($p);
    }
}
