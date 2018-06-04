<?php
namespace dxkite\support\visitor\response;

use ReflectionMethod;
use dxkite\support\visitor\exception\CallableException;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use dxkite\support\visitor\Context;
use dxkite\support\file\File;
use suda\exception\JSONException;
use dxkite\support\proxy\exception\ProxyException;
use dxkite\support\visitor\Permission;
use dxkite\support\visitor\exception\PermissionExcepiton;

abstract class CallableResponse extends MethodCallResponse
{
    protected $defaultParams=[MethodCallResponse::PARAM_JSON];
    protected $isrpc=false;
    protected $isJsonp=false;

    public function onVisit(Context $context)
    {
        $this->export=$this->getExportMethods();
        $this->isrpc=$this->getContext()->getRequest()->isPost()&&$this->getContext()->getRequest()->isJson();
        $method=$this->getContext()->getRequest()->get()->method($this->defaultMethod);
        $this->isrpc = $this->isrpc && !isset($this->export[$method]);
        $this->isJsonp= $this->getContext()->getRequest()->get('jsonp_callback', false);
        if ($this->isrpc) {
            try {
                $this->getContext()->getRequest()->json();
                parent::onVisit($context);
            } catch (JSONException $e) {
                return  $this->error("ParseError",$e->getCode(),  $e->getMessage());
            }
        } else {
            parent::onVisit($context);
        }
    }

    public function __default()
    {
        if ($this->isrpc) {
            $json=$this->getContext()->getRequest()->json();
            if ($this->isAssocArray($json)) {
                try {
                    return $this->returnJson($this->rpcCall($json));
                } catch (CallableException $e) {
                    return $this->returnJson($e->toArray());
                }
            } else {
                $result=[];
                foreach ($json as $call) {
                    try {
                        $result[]=$this->rpcCall($call);
                    } catch (CallableException $e) {
                        $result[]=$e->toArray();
                    }
                }
                $this->returnJson($result);
            }
        } else {
            return $this->returnJson($this->getHelpJson());
        }
    }

    protected function getHelpJson()
    {
        $methods=$this->getExportMethods();
        $help=[];
        foreach ($methods as $name=> $methodConfig) {
            $method=$this->getReflectionMethod($methodConfig['callback']);
            $docs=$method->getDocComment();
            $help[$name]=[];
            if ($docs) {
                $help[$name]['doc']=$docs;
            }
            else if (isset($methodConfig['comment'])){
                $help[$name]['comment']=$methodConfig['comment'];
            }
            if ($docs) {
                if (preg_match('/@ACL\s+([\w,]+)?\s*$/im', $docs, $match)) {
                    $acl=null;
                    if (isset($match[1])) {
                        $acl=explode(',', trim($match[1], ','));
                    }
                    $help[$name]['acl']=$acl;
                }
                if (preg_match('/@paramSource\s+([\w,]+)\s*$/ims', $docs, $match)) {
                    $types=explode(',', strtoupper(trim($match[1], ',')));
                    $help[$name]['paramSource']=$types;
                }
            }
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
        } elseif (is_array($method) && count($method)>1) {
            $method=new ReflectionMethod($method[0], $method[1]);
        } else {
            $method=new ReflectionFunction($method);
        }
        return $method;
    }

    public function runMethod($method_call, array $params)
    {
        // TODO: format check
        $param_arr=$this->isrpc?($params['params']??[]):$params;
        // 获取对象引用
        $method=$this->getReflectionMethod($method_call);
        // 参数检查
        if ($this->isAssocArray($param_arr)) {
            try {
                $param_arr=$this->paramsCheck($method, $param_arr);
            } catch (CallableException $e) {
                return $this->error($e->getName(),$e->getCode(), $e->getMessage(), $e->getData());
            }
        }
      
        try {
            if ($method->getShortName()==$this->defaultMethod) {
                return $this->{$this->defaultMethod}();
            } else {
                // 二进制文件
                if ($doc=$method->getDocComment()) {
                    if (preg_match('/@binary\s+([\w]+)?\s*$/im', $doc, $match)) {
                        if (isset($match[1])) {
                            $this->setHeader('Content-Type:'.$this->mime($match[1]), true);
                        }
                        parent::runMethod($method_call, $param_arr);
                        return;
                    }
                }
                $data=parent::runMethod($method_call, $param_arr);
                if ($data instanceof File) {
                    $this->setHeader('Content-Length:'.$data->getSize(), true);
                    $this->setHeader('Content-Type:'.$this->mime($data->getType()), true);
                    $content=file_get_contents($data->getPath());
                    echo $content;
                    return;
                }
            }
        } catch (\Error $e) {
            return  $this->error(get_class($e),$e->getCode(), $e->getMessage(), $param_arr);
        } catch (CallableException $e) {
            return $this->error($e->getName(), $e->getCode(), $e->getMessage());
        } catch (PermissionExcepiton $e) {
            return $this->error("PermissionDeny", $e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            return $this->error(get_class($e), $e->getCode(), $e->getMessage(), $param_arr);
        }
        return $this->returnJson($this->ArrayResult(0, $data));
    }

    protected function rpcRunMethod($method_call, array $param_arr)
    {
        try {
            $method=$this->getReflectionMethod($method_call);
        } catch (\ReflectionException $e) {
            throw (new CallableException($e->getMessage(), -32601))->setName('MethodNotFound');
        }
        if ($this->isAssocArray($param_arr)) {
            $param_arr=$this->paramsCheck($method, $param_arr);
        }
        try {
            // 二进制文件
            if ($doc=$method->getDocComment()) {
                if (preg_match('/@binary\s+([\w]+)?\s*$/im', $doc, $match)) {
                    ob_start();
                    parent::runMethod($method_call, $param_arr);
                    $data['binary']=base64_encode(ob_get_clean());
                    if (isset($match[1])) {
                        $data['type']=$this->mime($match[1]);
                        $this->setHeader('Content-Type:'.$data['type'], true);
                    } elseif (isset($this->type)) {
                        $data['type']=$this->type;
                        $this->setHeader('Content-Type:'.$data['type'], true);
                    }
                    $data['encode']='base64';
                    return $data;
                }
            }
            $data=parent::runMethod($method_call, $param_arr);
            if ($data instanceof File) {
                $result['binary']=base64_encode(file_get_contents($data->getPath()));
                $result['type']=$data->getType();
                $result['encode']='base64';
                return $result;
            }
        } catch (\TypeError $e) {
            throw (new CallableException('Invalid Params', -32602))->setName('InvalidParams');
        } catch (ProxyException $e) {
            throw (new CallableException($e->getMessage(), -32001+$e->getCode()))->setName('ServerError');
        } 
        return $data;
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
                    $typeName=is_object($val)?get_class($val):gettype($val);
                    try {
                        // 文件类型处理
                        $paramTypeName=$param->getType()->__toString();
                        if ($paramTypeName == File::class) {
                            if ($params[$name] instanceof File) {
                                $args[$pos]=$val;
                            } else {
                                $args[$pos]=File::createFromBase64($params[$name]['name'], $params[$name]['data']);
                            }
                        } elseif (settype($val, $paramTypeName)) {
                            $args[$pos]=$val;
                        } else {
                            throw (new CallableException(__('参数%s无法从%s转化成 %s 类型！', $name, $typeName, $paramTypeName), -32602))->setName('InvalidParams');
                        }
                    } catch (\CallableException $e) {
                        throw $e;
                    } catch (\Exception $e) {
                        throw (new CallableException(__('参数%s无法从%s转化成 %s 类型！', $name, $typeName, $paramTypeName), -32602))->setName('InvalidParams');
                    }
                } else {
                    $args[$pos]=$params[$name];
                }
            } elseif (!$param->isDefaultValueAvailable()) {
                throw (new CallableException(__('参数错误，需要参数: %s', $name), -32602))->setName('InvalidParams')->setData(['name'=>$name, 'pos'=>$pos]);
            }
        }
        return $args;
    }

    protected function rpcCall(array $callinfo)
    {
        if ($this->checkCallable($callinfo)) {
            try {
                if (isset($this->export[$callinfo['method']])) {
                    $result=$this->rpcRunMethod($this->export[$callinfo['method']]['callback'], $callinfo['params']);
                } else {
                    throw (new CallableException("Method:".$callinfo['method'], -32601))->setName('MethodNotFound');
                }
                return $this->ArrayResult($callinfo['id'], $result);
            } catch (CallableException $e) {
                throw $e->setId($callinfo['id']);
            }
        } else {
            throw (new CallableException('Invalid Request', -32600))->setName("InvalidRequest");
        }
    }

    protected function checkCallable(array $json)
    {
        return  isset($json['method']) && isset($json['params']) && isset($json['id']) && !is_null($json['id']);
    }

    protected function error(string $name, int $code,string $message, $data=null)
    {
        return $this->returnJson([
            'error'=>[
                'name'=>$name,
                'message'=>$message,
                'code'=>$code,
                'data'=>$data,
            ],
            'id'=>null
        ]);
    }

    protected function isAssocArray(array $array)
    {
        return !is_numeric(key($array));
    }
    protected function ArrayResult(int $id, $result)
    {
        return [
            'result'=>$result,
            'id'=>$id
        ];
    }
    protected function returnJson(array $json)
    {
        if ($callback=$this->getContext()->getRequest()->get('jsonp_callback')) {
            $this->type('js');
            echo $callback.'('.json_encode($json).');';
        } else {
            return $this->json($json);
        }
    }

    public function onDeny(Context $context)
    {
        throw (new CallableException('Permission Deny'))->setName("PermissionDeny");
    }

    protected function jsonParam($request)
    {
        if ($this->isJsonp) {
            if ($request->get('jsonp_call', false)) {
                return json_decode($request->get('jsonp_call'));
            } else {
                return $request->get()->_getVar();
            }
        }
        return parent::jsonParam($request);
    }
}
