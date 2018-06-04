<?php
namespace xctf\shop\response\user;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\Captcha;
use xctf\shop\table\UserTable;

class RegisterResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        $this->go(u('user'));
    }

    public function onGuestVisit(Context $context)
    {
        if (request()->method() === 'GET') {
            Captcha::generate();
            $this->page('register')
            // ->set('danger',1)->set('ip',1)
            ->set('ques', session()->get('question'))
            ->set('uuid', session()->get('uuid'))
            ->render();
        } else {
            // 验证失败或三次注册
            if (!Captcha::check() || session()->get('registered', 0) > 3) {
                debug()->warning('captcha failed');
                $page = $this->page('register');
                if (session()->get('registered', 0)  > 3) {
                    $page ->set('ip', 1);
                }
                return $page
                ->set('danger', 1)
                ->set('ques', session()->get('question'))
                ->set('uuid', session()->get('uuid'))
                ->render();
            }
            $username = request()->post('username');
            $mail = request()->post('mail');
            $password = request()->post('password');
            $password_confirm = request()->post('password_confirm');
            $invite_user = request()->post('invite_user');

            if ($password != $password_confirm) {
                debug()->warning('password_confirm');
                return $this->page('register')
                ->set('danger', 1)
                ->set('ques', session()->get('question'))
                ->set('uuid', session()->get('uuid'))
                ->render();
            }
            $user= new UserTable;
            if ($mail && $username && $password) {
                if (!$user->listWhere(['username' => $username])) {
                    if ($user->insert([
                        'username' => $username,
                        'password'=>$password,
                        'mail'=>$mail,
                        'wallet'=> preg_replace('/=|\+|\//', '', base64_encode(hex2bin(hash('sha256', conf('walletSeed', 'dx').microtime())))),
                        'integral'=>conf('defaultIntegral', 1000),
                    ])) {
                        debug()->warning($username.' registed');
                        $this->go(u('login'));
                        session()->set('registered', session()->get('registered', 0)+1);
                        if ($invite_user != $username) {
                            debug()->warning('invite from '.$invite_user);
                            if ($invite=$user->select(['id','integral','invite'], ['username' => $invite_user])->fetch()) {
                                debug()->warning('invited '.$invite_user.' invite '.$username);
                                $user->updateByPrimaryKey($invite['id'], ['integral'=> $invite['integral']+10 ,'invite'=> $invite['invite'] + 1]);
                            }
                        }
                    }
                }
            } else {
                debug()->warning('infomation miss');
                return $this->page('register')
                ->set('danger', 1)
                ->set('ques', session()->get('question'))
                ->set('uuid', session()->get('uuid'))
                ->render();
            }
        }
    }
}
