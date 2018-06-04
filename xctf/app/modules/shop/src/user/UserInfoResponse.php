<?php
namespace xctf\shop\response\user;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;

use xctf\shop\table\UserTable;
use xctf\shop\Block;


class UserInfoResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        $user=new UserTable;
        $userInfo=$user->getByPrimaryKey(get_user_id());
        $count=Block::count(get_user_id());
        
        $this->page('user')
        ->set('user',$userInfo)
        ->set('flagcoin',$count)
        ->render();
    }
}
