<?php
namespace dxkite\support\visitor;

class Visitor
{
    const  simulateUserToken='__suid';
    protected $id;
    protected $token;
    protected $permission=null;
    protected $isGuest=true;
    protected $simulate=null;
    const  MASK=0x19980602;

    public function __construct(string $token=null)
    {
        if ($token&&$get=self::decodeToken($token)) {
            list($this->id, $this->token)=$get;
            $this->isGuest=!$this->check($this->id, $this->token);
        } else {
            $this->isGuest=true;
        }
        
        if ($this->isGuest) {
            $this->id=0;
            $this->token=md5('Suda-Guest-Visitor-'.PHP_VERSION.SUDA_VERSION);
            $this->permission=new Permission;
        }
    }

    public function getId()
    {
        if ($this->hasPermission('visitor.simulate') && cookie()->has(self::simulateUserToken)) {
            $userId=intval(cookie()->get(self::simulateUserToken, $this->id));
            debug()->trace(__('user_simulated  %d --> %d', $this->id, $userId));
            if ($this->isSimulateMode()) {
                $this->refreshPermission($userId);
            }
            return $userId;
        }
        return $this->isSimulateMode()?$this->simulate:$this->id;
    }
    
    public function isSimulateMode()
    {
        return $this->simulate != $this->simulate;
    }
    
    public function simulateUser(int $userId)
    {
        cookie()->set(self::simulateUserToken, $userId);
    }

    public function clearSimulateMode()
    {
        $this->refreshPermission($this->id);
    }

    // 设置登陆状态
    public function signin(int $id, int $expireTime=3600, bool  $remember=false)
    {
        // 生成TOKEN
        $token=md5($id.microtime());
        // 过期时间
        $expireTime=time()+$expireTime;
        // 刷新状态
        if ($session = table('session')->query('SELECT id,expire FROM #{@table@} WHERE grantee = :grantee and expire > :expire', [
            'grantee'=>$id,
            'expire'=>time(),
        ])->fetch()) {
            $beat=table('session')->updateByPrimaryKey($session['id'], ['token'=>$token,'expire'=>$session['expire']+$expireTime]);
        } else {
            $beat=table('session')->insert([
                'grantee'=>$id,
                'expire'=> $expireTime,
                'ip'=>request()->ip(),
                'time'=>time(),
                'token'=> $token,
            ]);
        }
        if ($beat) {
            Context::getInstance()->setSession('expireTime', $expireTime);
            $this->refresh($id, $token);
            Context::getInstance()->cookieVisitor($this)->expire($expireTime)->session(!$remember)->set();
        }
        return $this;
    }
    
    public function beat(int $userId, int $expireTime=3600)
    {
        $expireTime= Context::getInstance()->getSession('expireTime', 0);
        // 最后一分钟内可以刷新
        if ($expireTime && $expireTime - time()  < conf('session.beat', 60)) {
            if ($session = table('session')->query('SELECT id,expire FROM #{@table@} WHERE grantee = :grantee and expire > :expire', [
                'grantee'=>$userId,
                'expire'=>time(),
            ])->fetch()) {
                return table('session')->updateByPrimaryKey($session['id'], ['expire'=>$session['expire']+$expireTime]);
            }
        }
        return false;
    }
    
    public function signout()
    {
        // 刷新状态
        if ($session = table('session')->query('SELECT id,expire FROM #{@table@} WHERE grantee = :grantee and expire > :expire', [
            'grantee'=>$this->id,
            'expire'=>time(),
        ])->fetch()) {
            table('session')->updateByPrimaryKey($session['id'], ['expire'=>time()]);
            return true;
        }
        return false;
    }
        
    // 检查是否是登陆状态
    protected function check(int $id, string $token)
    {
        if ($session = table('session')->query('SELECT id FROM #{@table@} WHERE grantee = :grantee and token = :token and expire > :expire', [
            'grantee'=>$id,
            'token'=>$token,
            'expire'=>time()
        ])->fetch()) {
            $this->refreshPermission($id);
            $this->beat($id);
            return true;
        }
        return false;
    }

    /**
     * 刷新权限
     *
     * @param integer $id
     * @return void
     */
    protected function refreshPermission(int $id)
    {
        $grant=table('grant')->getTableName();
        $permissions=table('role')->query('SELECT permission FROM #{@table@} JOIN  #{'.$grant.'} ON #{'.$grant.'}.grant = #{@table@}.id WHERE grantee = :grantee', ['grantee'=>$id])->fetchAll();
        if ($permissions) {
            $permission=new Permission;
            foreach ($permissions as $item) {
                if ($item['permission'] instanceof Permission) {
                    $permission->merge($item['permission']);
                }
            }
            $this->setPermission($permission);
        } else {
            $this->setPermission(new Permission);
        }
        $this->simulate = $id;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function refresh(int $id, string $token)
    {
        $this->id=$id;
        $this->token=$token;
        $this->isGuest=false;
        $this->refreshPermission($id);
        return $this;
    }

    public function setPermission(Permission $permission)
    {
        $this->permission=$permission;
        return $this;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function isGuest()
    {
        return $this->isGuest;
    }

    public function getMaskToken()
    {
        return $this->encodeToken($this->id, $this->token);
    }

    public function canAccess($method)
    {
        if ($permission=Permission::createFromFunction($method)) {
            return $this->hasPermission($permission);
        }
        return true;
    }

    public function hasPermission($permission)
    {
        if (!$permission instanceof Permission) {
            if (is_array($permission)) {
                $permission=new Permission($permission);
            } elseif (is_string($permission)) {
                $permission=new Permission([$permission]);
            } else {
                return false;
            }
        }
        $check=$this->getPermission()->surpass($permission);
        debug()->trace(__('check_access %d', $check), ['visitor'=>$this->getPermission(),'need'=>$permission]);
        return $check;
    }


    private static function encodeToken(int $id, string $token)
    {
        // 32-bit-Pack
        $idnum=bin2hex(pack('N', $id^Visitor::MASK));
        return base64_encode(hex2bin($idnum.$token));
    }

    private static function decodeToken(string $tokenstr)
    {
        $tokenstr=bin2hex(base64_decode($tokenstr));
        if (strlen($tokenstr)!=40) {
            return false;
        }
        $idnum=substr($tokenstr, 0, 8);
        $id=unpack('Nid', hex2bin($idnum))['id'];
        return [$id^Visitor::MASK,substr($tokenstr, 8, 32)];
    }

    public static function initVisitor()
    {
        Context::getInstance()->loadVisitorFromCookie();
    }

    public static function initLocate()
    {
        $locate = cookie()->get(conf('session.language','__lang'),conf('app.language', 'zh-CN'));
        \suda\core\Locale::set($locate);
    }
}
