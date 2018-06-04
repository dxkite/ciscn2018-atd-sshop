<?php
namespace xctf\shop\response\user;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\Captcha;
use xctf\shop\table\UserTable;

class ChangePasswordResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        if (request()->method() === 'GET') {
            $this->page('change')->render();
        } else {
            $old_password = request()->post('old_password');
            $password = request()->post('password');
            $password_confirm = request()->post('password_confirm');
            if ($password == $password_confirm) {
                $user= new UserTable;
                if ($userInfo=$user->getByPrimaryKey(get_user_id())) {
                    if (password_verify($old_password, $userInfo['password'])) {
                        $user->updateByPrimaryKey(get_user_id(), [
                            'password'=>$password,
                        ]);
                        return  $this->page('change')->set('success',1)->render();
                    }
                }
            }
            return  $this->page('change')->render();
        }
    }
}
