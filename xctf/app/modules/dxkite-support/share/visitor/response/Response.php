<?php
namespace dxkite\support\visitor\response;

use suda\core\Request;
use dxkite\support\visitor\Context;
use dxkite\support\visitor\Visitor;
use dxkite\support\visitor\Permission;

abstract class Response extends \suda\core\Response
{
    private $context;
    
    final public function onRequest(Request $request)
    {
        $context=Context::getInstance();
        $this->context=$context;
        $context->setRequest($request);
        if ($context->getVisitor()->canAccess([ $this,'onVisit'])) {
            $this->onVisit($context);
        } else {
            $this->onDeny($context);
        }
    }
    
    abstract public function onVisit(Context $context);

    public function onDeny(Context $context)
    {
        $route=config()->get('deny_access');
        if ($route) {
            $this->go(u($route));
        } else {
            $this->etag(md5(time()));
            echo '<h1>deny access</h1>';
        }
    }
    
    public function getContext()
    {
        return $this->context;
    }
}
