<?php

namespace dxkite\support\visitor\response;

use ReflectionMethod;
use dxkite\support\visitor\exception\RPCException;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use dxkite\support\visitor\Context;
use dxkite\support\exception\JSONException;

abstract class PRCCallResponse extends Response
{
    protected $defaultParams=[MethodCallResponse::PARAM_JSON];

    public function onVisit(Context $context)
    {
        $this->isrpc=$this->getContext()->getRequest()->isJson() && $this->getContext()->getRequest()->isPost();
        $method=$method=$this->getContext()->getRequest()->get()->method($this->defaultMethod);
        $this->isrpc = $this->isrpc && !isset($this->export[$method]);
        if ($context->getRequest()->isGet()) {
            return $this->json($this->getHelpJson());
        } else {
            try {
                $this->getContext()->getRequest()->json();
                $this->onCall($context);
            } catch (JSONException $e) {
                return  $this->rpcError(-32700, 'Parse Error', $e->getMessage());
            }
        }
    }

    public function onCall()
    {
        $json=$this->getContext()->getRequest()->json();
        if ($this->isAssocArray($json)) {
            try {
                return $this->rpcJson($this->rpcCall($json));
            } catch (RPCException $e) {
                return $this->rpcJson($e->toArray());
            }
        } else {
            $result=[];
            foreach ($json as $call) {
                try {
                    $result[]=$this->rpcCall($call);
                } catch (RPCException $e) {
                    $result[]=$e->toArray();
                }
            }
            $this->rpcJson($result);
        }
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

    protected function rpcRunMethod($method_call, array $param_arr)
    {
        try {
            $method=$this->getReflectionMethod($method_call);
        } catch (\ReflectionException $e) {
            throw new RPCException('Method Not Found', -32601);
        }
        if ($this->isAssocArray($param_arr)) {
            try {
                $param_arr=$this->paramsCheck($method, $param_arr);
            } catch (RPCException $e) {
                throw new RPCException('Invalid Params', -32602);
            }
        }
        try {
            // 二进制文件
            if ($doc=$method->getDocComment()) {
                if (preg_match('/@binary\s+([\w]+)?\s*$/im', $doc, $match)) {
                    ob_start();
                    parent::runMethod($method_call, $param_arr);
                    $data['binary']=base64_encode(ob_get_clean());
                    if (isset($match[1])) {
                        $data['type']=$match[1];
                        $data['encode']='base64';
                    }
                    return $data;
                }
            }
            $data=parent::runMethod($method_call, $param_arr);
        } catch (\TypeError $e) {
            throw new RPCException('Invalid Params', -32602);
        } catch (RPCException $e) {
            throw (new RPCException('Internal Error', -32603))->setData($e->getMessage());
        } catch (Exception $e) {
            throw (new RPCException('Server Error', -32000))->setData($e->getMessage());
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
                    if (@settype($val, $param->getType()->__toString())) {
                        $args[$pos]=$val;
                    } else {
                        throw (new RPCException(__('参数 %s 无法转化成 %s 类型！', $name, $param->getType()->__toString()), -1))->setName('paramTypeCastException');
                    }
                } else {
                    $args[$pos]=$params[$name];
                }
            } elseif (!$param->isDefaultValueAvailable()) {
                throw (new RPCException(__('参数错误，需要参数: %s', $name), -1))->setName('paramError')->setData(['name'=>$name, 'pos'=>$pos]);
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
                    throw new RPCException('Method Not Found', -32601);
                }
                return $this->rpcReturn($callinfo['id'], $result);
            } catch (RPCException $e) {
                throw $e->setId($callinfo['id']);
            }
        } else {
            throw new RPCException('Invalid Request', -32600);
        }
    }

    protected function checkCallable(array $json)
    {
        return isset($json['jsonrpc']) && $json['jsonrpc'] =='2.0'&& isset($json['method']) && isset($json['params']) && isset($json['id']) && !is_null($json['id']);
    }

    protected function rpcError(int $code, string $message, $data)
    {
        $this->type('json');
        return $this->json([
            'jsonrpc'=>'2.0',
            'error'=>[
                'code'=>$code,
                'message'=>$message,
                'data'=>$data,
            ],
            'id'=>null
        ]);
    }

    protected function isAssocArray(array $array)
    {
        return !is_numeric(key($array));
    }

    protected function rpcReturn(int $id, $result)
    {
        $this->type('json');
        return [
            'jsonrpc'=>'2.0',
            'result'=>$result,
            'id'=>$id
        ];
    }

    protected function rpcJson(array $json)
    {
        if ($callback=$this->getContext()->getRequest()->get('jsonp_callback')) {
            $this->type('js');
            echo $callback.'('.json_encode($json).');';
        } else {
            return $this->json($json);
        }
    }
}
