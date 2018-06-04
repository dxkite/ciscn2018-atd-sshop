<?php
namespace dxkite\support\visitor;

use suda\core\Request;
use suda\core\Session;
use suda\core\Cookie;
use suda\core\Storage;
use dxkite\support\visitor\Visitor;

class Context
{
    private $request;
    private $sessionId;
    private $cookieName='__visitor';
    private $requestSession=true;
    private $visitor=null;

    protected static $instance=null;

    protected function __construct()
    {
        $this->initSession();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance=new  Context;
        }
        return self::$instance;
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function setRequest(Request $request)
    {
        $this->request=$request;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setVisitor(Visitor $visitor)
    {
        $this->visitor=$visitor;
        return $this;
    }

    public function getVisitor():Visitor
    {
        return $this->visitor;
    }

    public function loadVisitorFromCookie()
    {
        $name=$this->getCookieName();
        if (Cookie::has($name)) {
            $visitor=new Visitor(Cookie::get($name));
            debug()->trace(__('load_from_cookie %d:%s token %s', $visitor->getId(), $visitor->getToken(), $visitor->getMaskToken()));
        } else {
            $visitor=new Visitor;
        }
        $this->visitor=$visitor;
    }

    public static function encodeCookieName(string $name, string $mask)
    {
        $mask=md5($mask, true);
        $name=pack('A*', $name);
        return bin2hex($mask^$name);
    }

    public static function decodeCookieName(string $data, string $mask)
    {
        $mask=md5($mask, true);
        if ($hash=hex2bin($data)) {
            return unpack('A*name', $hash^$mask)['name'];
        }
        return false;
    }

    public function setSession(string $name, $value)
    {
        $_SESSION[$name]=$value;
        return isset($_SESSION[$name]);
    }

    public function getSession(string $name='', $default=null)
    {
        if ($name) {
            return isset($_SESSION[$name])?$_SESSION[$name]:$default;
        } else {
            return $_SESSION;
        }
    }
    
    public function delSession(string $name)
    {
        unset($_SESSION[$name]);
    }

    public function hasSession(string $name)
    {
        return session_is_registered($name);
    }

    public function destroySession()
    {
        session_unset();
    }

    public function cookieVisitor(Visitor $visitor)
    {
        return Cookie::set($this->getCookieName(), $visitor->getMaskToken());
    }
    
    public function getCookieName()
    {
        return $this->encodeCookieName($this->cookieName, $this->sessionId);
    }

    private function initSession()
    {
        $path=DATA_DIR.'/session';
        if ($this->requestSession) {
            $id=md5(conf('session.secret', ROOT_PATH.SUDA_VERSION).request()->ip());
            hook()->exec('support:sessionId::init', [&$id]);
            session_id($id);
            $this->requestSessionf=false;
        }
        if (storage()->mkdirs($path)) {
            session_save_path($path);
        }
        session_name(conf('session.name', '__session'));
        session_cache_limiter(conf('session.limiter', 'private'));
        session_cache_expire(conf('session.expire', 0));
        session_start();
        $this->sessionId=session_id();
        debug()->trace('start session '.$this->sessionId);
    }
}
