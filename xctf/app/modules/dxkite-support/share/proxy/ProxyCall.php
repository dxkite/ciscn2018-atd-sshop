<?php
namespace dxkite\support\proxy;

use dxkite\support\proxy\Proxy;
use suda\template\compiler\suda\Compiler;


class ProxyCall
{
    const ROW=0;
    const ROWS=1;
    public static function addProxyCall(Compiler $compiler)
    {
        // 添加DAO
        $compiler->addCommand('call', function ($exp) {
            if ($exp) {
                $params=static::param($exp);
                $type=strtoupper($params['type']??'rows');
                $method=static::echoValue($params['method']);
                $class=trim($params['class'], '"\'');
                $null=trim($params['null']??'this is null', '"\'');
                return '<?php '.__CLASS__.'::call(\''.$class.'\',$this,function($class,$page,$type){
    $fields=$class->'.$method.'; 
    $type='.__CLASS__.'::'.$type.';
    $null=\''. $null .'\';
    $callback=function(array $field) { extract($field); ?>';
            } else {
                return '<?php };
    if ($fields){
        if ($type==='.__CLASS__.'::ROWS){
            foreach ($fields as $field){
                $callback($field);
            }
        }
        else{
            $callback($fields);
        }     
    } else {
        echo $null; 
    }
});?>';
            }
        });
    }
    public static function param(string $exp)
    {
        $exp=preg_replace('/\((.+)\)/', '$1', $exp);
        $exp=trim(trim($exp), ';');
        $sets=explode(';', $exp);
        $values=[];
        foreach ($sets as $str) {
            list($key, $value)=explode(':', $str, 2);
            $values[trim($key)]=trim($value);
        }
        return $values;
    }
    
    public static function echoValue($var)
    {
        return preg_replace_callback('/\B[$][:]([.\w\x{4e00}-\x{9aff}]+)(\s*)(\( ( (?>[^()]+) | (?3) )* \) )?/ux', function ($matchs) {
            $name=$matchs[1];
            $args=isset($matchs[4])?','.$matchs[4]:'';
            return '$this->get("'.$name.'"'.$args.')';
        }, $var);
    }

    public static function call(string $proxyname, $template, $callback)
    {
        $class=new Proxy(new $proxyname($template->getResponse()->getContext()));
        return $callback($class, $template, $callback);
    }
}
