<?php
namespace dxkite\support\visitor\response;

use dxkite\support\visitor\Context;
use dxkite\support\visitor\Visitor;


abstract class VisitorResponse extends Response
{
    final public function onVisit(Context $context)
    {
        // $this->onUserVisit($context);
        if ($context->getVisitor()->isGuest()) {
            $this->onGuestVisit($context);
        } elseif ($context->getVisitor()->canAccess([$this,'onUserVisit'])) {
            $this->onUserVisit($context);
        } else {
            $this->onDeny($context);
        }
    }

    public function onGuestVisit(Context $context)
    {
        $route=config()->get('user_signin_route');
        cookie()->set('redirect_uri',u($_GET));
        $this->go(u($route));
    }
    
    abstract public function onUserVisit(Context $context);
}
