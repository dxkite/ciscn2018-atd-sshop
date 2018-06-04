<?php
namespace xctf\shop\response\user;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\Captcha;
use xctf\shop\table\UserTable;

class UserLoginResponse extends VisitorResponse
{

    public function onUserVisit(Context $context)
    {
        $this->go(u('user'));
    }

    public function onGuestVisit(Context $context)
    {
        if (request()->method() === 'GET') {
            Captcha::generate();
            $this->page('login')
            ->set('ques', session()->get('question'))
            ->set('uuid', session()->get('uuid'))
            ->render();
        } else {
            if (!Captcha::check()) {
                return $this->page('login')
                ->set('danger', 1)
                ->set('ques', session()->get('question'))
                ->set('uuid', session()->get('uuid'))
                ->render();
            }
            $username = request()->post('username');
            $password = request()->post('password');

            $user= new UserTable;
            if ($username && $password) {
                if ($userInfo=$user->select(['password','id'],['username' => $username])->fetch()) {
                    if(password_verify($password,$userInfo['password'])){
                        visitor()->signin($userInfo['id']);
                        $this->go(u('user'));
                        return;
                    }
                } 
                return $this->page('login')
                    ->set('danger', 1)
                    ->set('ques', session()->get('question'))
                    ->set('uuid', session()->get('uuid'))
                    ->render();
            } else {
                return $this->page('login')
                ->set('danger', 1)
                ->set('ques', session()->get('question'))
                ->set('uuid', session()->get('uuid'))
                ->render();
            }
        }
    }
}
