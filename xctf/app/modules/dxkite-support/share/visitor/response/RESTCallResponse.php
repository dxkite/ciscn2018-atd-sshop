<?php

namespace dxkite\support\visitor\response;

use ReflectionMethod;
use dxkite\support\visitor\exception\RESTCallException;
use dxkite\support\visitor\exception\RPCException;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use dxkite\support\visitor\Context;
use dxkite\support\exception\JSONException;

abstract class RESTCallResponse extends MethodCallResponse
{
    protected $defaultParams=[MethodCallResponse::PARAM_JSON];

    public function __construct()
    {
        parent::__construct();
    }

    public function __default()
    {
        return $this->json($this->getHelpJson());
    }

    protected function getHelpJson()
    {
        $methods=$this->getExportMethods();
        $help=[];
        foreach ($methods as $method) {
            $method=$this->getReflectionMethod($method['callback']);
            $name=$method->getShortName();
            $help[$name]['doc']=$method->getDocComment();
            foreach ($method->getParameters() as $param) {
                $help[$name]['parameters'][$param->getName()]['pos']=$param->getPosition();
                if ($param->hasType()) {
                    $help[$name]['parameters'][$param->getName()]['type']=$param->getType()->__toString();
                }
                if ($param->isDefaultValueAvailable()) {
                    $help[$name]['parameters'][$param->getName()]['default']=$param->getDefaultValue();
                }
            }
        }
        return $help;
    }
    
    protected function getReflectionMethod($method)
    {
        if ($method instanceof \ReflectionMethod || $method instanceof \ReflectionFunction) {
        } elseif (count($method)>1) {
            $method=new ReflectionMethod($method[0], $method[1]);
        } else {
            $method=new ReflectionFunction($method);
        }
        return $method;
    }

    public function runMethod($method_call, array $param_arr)
    {
        // 获取对象引用
        $method=$this->getReflectionMethod($method_call);
        // 参数检查
        try {
            $param_arr=$this->paramsCheck($method, $param_arr);
        } catch (RESTCallException $e) {
            return $this->return($e->getData(), $e->getName(), $e->getMessage(), $e->getCode());
        }
        try {
            if ($method->getShortName()==$this->defaultMethod) {
                return $this->{$this->defaultMethod}();
            } else {
                // 二进制文件
                if ($doc=$method->getDocComment()) {
                    if (preg_match('/@binary\s+([\w]+)?\s*$/im', $doc, $match)) {
                        if (isset($match[1])) {
                            $data['type']=$match[1];
                            $this->type($match[1]);
                        }
                        parent::runMethod($method_call, $param_arr);
                        return;
                    }
                }
                $data=parent::runMethod($method_call, $param_arr);
            }
        } catch (RESTCallException $e) {
            return $this->return($e->getData(), $e->getName(), $e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return $this->return($param_arr, get_class($e), $e->getMessage(), $e->getCode());
        }
        return $this->return($data);
    }

    protected function paramsCheck($method, array  $params)
    {
        $args=[];
        // 压入调用参数
        foreach ($method->getParameters() as $param) {
            $name=$param->getName();
            $pos=$param->getPosition();
            if (isset($params[$name])) {
                if ($param->hasType()) {
                    $val=$params[$name];
                    if (@settype($val, $param->getType()->__toString())) {
                        $args[$pos]=$val;
                    } else {
                        throw (new RESTCallException(__('参数 %s 无法转化成 %s 类型！', $name, $param->getType()->__toString()), -1))->setName('paramTypeCastException');
                    }
                } else {
                    $args[$pos]=$params[$name];
                }
            } elseif (!$param->isDefaultValueAvailable()) {
                throw (new RESTCallException(__('参数错误，需要参数: %s', $name), -1))->setName('paramError')->setData(['name'=>$name, 'pos'=>$pos]);
            }
        }
        return $args;
    }

    protected function return($data, string $error=null, string $message=null, int $erron=0)
    {
        return $this->json([
            'error'=>$error,
            'errno'=>$erron,
            'message'=>$message,
            'data'=>$data,
        ]);
    }
}
