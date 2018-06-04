<?php
namespace dxkite\support\api;
use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;
use suda\core\route\Mapping;

class MenuResponse extends Response
{
    public function onVisit(Context $context){
        $param=Mapping::$current->getParam();
        $apiMenu = [];
        foreach ($param['mappingSets'] as $name => $mapping){
            $apiMenu[$name]['url']=$mapping->getUrlTemplate();
            $apiMenu[$name]['link']=$mapping->createUrl([]);
        }
        $this->json($apiMenu);
    }
}