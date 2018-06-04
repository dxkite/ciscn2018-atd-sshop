<?php
namespace xctf\shop\response\user;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;

class UserLogoutResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        visitor()->signout();
        $this->go(u('login'));
    }
}
