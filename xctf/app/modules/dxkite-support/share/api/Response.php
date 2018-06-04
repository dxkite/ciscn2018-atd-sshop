<?php
namespace dxkite\support\api;
use dxkite\support\visitor\response\CallableResponse;
use suda\core\route\Mapping;

class Response extends CallableResponse
{
    public function getExportMethods($class=NULL)
    {
        $param=Mapping::$current->getParam();
        return parent::getExportMethods($param['proxyClass']);
    }
}