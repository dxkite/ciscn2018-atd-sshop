<?php
namespace dxkite\support\proxy;

use dxkite\support\proxy\exception\ProxyException;
use dxkite\support\visitor\Context;

class ProxyInstance
{
    protected static $tableInstances;
    protected static $instance;
    protected static $proxyClass;

    protected function __construct()
    {
        $this->getProxyNames();
    }

    public static function new(string $proxyName)
    {
        $proxyClassName=class_name(self::instance()->getClassName($proxyName));
        return self::$tableInstances[$proxyName]=new Proxy(new $proxyClassName);
    }

    public static function newUrlObject(string $proxyName, bool $outputFile=false)
    {
        $proxyClassName=self::instance()->getClassName($proxyName);
        return self::$tableInstances[$proxyName]=new \dxkite\support\api\Method($proxyClassName, $outputFile);
    }

    public static function getInstance(string $proxyName, bool  $outputFile=false)
    {
        if (isset(self::$tableInstances[$proxyName])) {
            return self::$tableInstances[$proxyName];
        }
        $proxyClassName=self::instance()->getClassName($proxyName);
        if (preg_match('/^https?/', $proxyClassName)) {
            if (conf('proxy.hash', false)) {
                return self::newUrlObject($proxyName, $outputFile);
            }
            $proxyClass = preg_replace('/^https?.+?\#/', '', $proxyClassName);
        }
        return  self::new($proxyName);
    }

    protected static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance=new self;
        }
        return self::$instance;
    }

    protected function getProxyNames()
    {
        $modules = app()->getLiveModules();
        foreach ($modules as $module) {
            $this->getProxyNameFromModule($module);
        }
    }

    protected function getProxyNameFromModule(string $module)
    {
        $config=app()->getModuleConfig($module);
        if (isset($config['proxy'])) {
            foreach ($config['proxy'] as $name=>$class) {
                self::$proxyClass[$name]=$class;
            }
        }
    }
    
    protected function getClassName(string $proxyName)
    {
        if (isset(self::$proxyClass[$proxyName])) {
            return self::$proxyClass[$proxyName];
        } else {
            throw new ProxyException(__('proxy %s class not exist', $proxyName));
        }
    }
}
