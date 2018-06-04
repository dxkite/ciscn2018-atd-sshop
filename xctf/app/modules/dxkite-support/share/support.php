<?php

function proxy(string $tableName, bool $outputFile=false)
{
    return dxkite\support\proxy\ProxyInstance::getInstance($tableName, $outputFile);
}


function invoke($class)
{
    if (is_string($class)) {
        $class=new $class;
    }
    return new dxkite\support\proxy\Proxy($class);
}

function context()
{
    return dxkite\support\visitor\Context::getInstance();
}

function visitor()
{
    return context()->getVisitor();
}

function get_user_id()
{
    return dxkite\support\proxy\ProxyObject::getUserId();
}

function has_permission($p)
{
    return dxkite\support\proxy\ProxyObject::hasPermission($p);
}

function setting(string $name, $default=null)
{
    return dxkite\support\setting\Setting::get($name, $default);
}

function setting_set(string $name, $value)
{
    return dxkite\support\setting\Setting::set($name, $value);
}
