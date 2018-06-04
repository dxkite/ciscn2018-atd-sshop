<?php
namespace xctf\shop\response\user;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;
use xctf\shop\Captcha;

class ResetPasswordResponse extends Response
{
    public function onVisit(Context $context)
    {
        if (request()->method() === 'GET' ){
            Captcha::generate();
            $this->page('reset')
            ->set('ques', session()->get('question'))
            ->set('uuid', session()->get('uuid'))
            ->render();
        }else{
            if (Captcha::check()) {
                $this->go(u('login'));
            }else{
                return $this->page('reset')
                ->set('ques', session()->get('question'))
                ->set('uuid', session()->get('uuid'))
                ->render();
            }
        }
    }
}
