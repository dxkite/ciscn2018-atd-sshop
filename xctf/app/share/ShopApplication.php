<?php
namespace xctf\shop;

use suda\core\Application;
use suda\core\Request;
use suda\core\Response;
use xctf\shop\Block;

class ShopApplication extends Application
{
    protected static $token = null;

    public function init()
    {
        parent::init();
        config()->set('user_signin_route', 'shop:login');
        $path = $this->getModulePath('shop').'/resource/captcha';
        config()->set('captcha.ans', $path.'/ans');
        config()->set('captcha.ques', $path.'/jpgs');
    }

    public static function htmlCsrfGenrate()
    {
        $csrfName=conf('csrfName', '_csrf');
        if (is_null(self::$token)) {
            self::$token = base64_encode(hex2bin(md5(microtime(true). memory_get_usage())));
            session()->set($csrfName, self::$token);
            cookie()->set($csrfName, self::$token);
            debug()->warning('generate '.$csrfName.' = '.self::$token);
        }
    }

    public function onRequest(Request $request)
    {
        if (visitor()->isGuest()) {
            debug()->warning('visitor is', 'Guest');
        } else {
            $count=Block::count(get_user_id());
            config()->set('flagEnough', $count >= conf('flagCoin'));
            debug()->warning('visitor is user ', visitor()->getId());
        }
        // config()->set('flagEnough', 1);
        if (in_array($request->method(), ["PUT","POST","DELETE"])) {
            $csrfName=conf('csrfName', '_csrf');
            $csrf = $request->get($csrfName, $request->post($csrfName));
            debug()->warning('post '.$csrfName.' - '.$csrf);
            if ($token=session()->get($csrfName, false)) {
                if ($token !== $csrf) {
                    debug()->warning('csrf check failed '.$csrfName.' = '.$csrf.' --> '.$token);
                    config()->set('onRequestError.isCsrfError', true);
                    return false;
                }
            }
        }
        if ($router=router()->parseUrl($request->url())) {
            if ($router->is('shop:dig') && self::isCcAttack()) {
                config()->set('onRequestError.isCcAttack', true);
                return false;
            }
        }
        return true;
    }
    
    public static function htmlCsrfFormError()
    {
        $render=new class extends Response {
            public function onRequest(Request $request)
            {
                $this->state(403);
                if (conf('onRequestError.isCsrfError', false)) {
                    echo '<h1>Suda: CSRF Defense </h1>';
                } elseif (conf('onRequestError.isCcAttack', false)) {
                    echo '<h1>Suda: CC Attack Defense</h1>';
                } else {
                    echo '<h1>Suda: Request Deny </h1>';
                }
            }
        };
        $render->onRequest(Request::getInstance());
        return true;
    }

    public static function isCcAttack()
    {
        $name =conf('cc.name', '__cc_protected');
        $time =conf('cc.time', 5);
        $times =conf('cc.times', 3);
        if (cookie()->has($name)) {
            session()->set('cc_protected', session()->get('cc_protected', 0)+1);
            if (session()->get('cc_protected', 0) > $times) {
                return  true;
            }
        } else {
            cookie()->set($name, time(), time()+$time)->set();
            session()->set('cc_protected', 0);
        }
        return false;
    }

    public static function htmlCsrfFormRegister($template)
    {
        $template->addCommand('htmlCsrfForm', function () {
            return '<?php echo '.__CLASS__.'::_htmlCsrfForm(); ?>';
        });

        $template->addCommand('float', function ($match) {
            return '<?php echo '.__CLASS__.'::_float'.$match.'; ?>';
        });
    }

    public static function _htmlCsrfForm()
    {
        ShopApplication::htmlCsrfGenrate();
        $name=conf('csrfName', '_csrf');
        return '<input name="'.$name.'" value="'. self::$token .'" hidden />';
    }

    public static function _float($input)
    {
        echo sprintf('%1.2f', $input);
    }
}
