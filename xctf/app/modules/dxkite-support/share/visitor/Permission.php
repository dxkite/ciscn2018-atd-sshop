<?php
namespace dxkite\support\visitor;

use suda\core\Application;
use suda\core\Storage;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionClass;
use suda\tool\Json;

/**
 * 二级权限验证，支持忽略父级元素
 */
class Permission implements \JsonSerializable
{
    // 权限表,包含所有的权限结构
    private static $permissionTable=[];
    // 权限配置
    private static $permissionConfig=[];
    // 所有权限列表，过滤用
    private static $permissionFilter=[];
    // 是否读取了权限表
    private static $readtable=false;
    // 私有权限（完整权限）
    private $permissions=[];

    public function __construct(array $permissions=null)
    {
        if (!self::$readtable) {
            self::readPermissions();
            self::$readtable=true;
        }
        if (!empty($permissions)) {
            // 字符串数组
            if (is_string(current($permissions))) {
                $this->permissions=$this->filter($permissions);
            } elseif (current($permissions) instanceof Permission) {
                // 合并权限
                $this->mergeArrays($permissions);
            }
        }
    }

    /**
     * 添加权限
     *
     * @param string $name 父级权限
     * @param array $permissions 子集权限
     * @return void
     */
    public static function set(string $name, array $permissions)
    {
        self::$permissionFilter[]=$name;
        foreach ($permissions as $permission) {
            self::$permissionFilter[]=$name.'.'.$permission;
        }
        self::$permissionFilter=array_merge(self::$permissionFilter, $permissions);
        self::$permissionTable[$name]=$permissions;
    }
    
    public function merge(Permission $anthor_vargs)
    {
        $anthor_vargs=func_get_args();
        $this->mergeArrays($anthor_vargs);
    }

    private function mergeArrays(array $anthor_vargs)
    {
        foreach ($anthor_vargs as $anthor) {
            if ($anthor instanceof Permission) {
                $this->permissions=array_merge($this->permissions,$anthor->permissions);
            }
        }
    }

    public function surpass(Permission $anthor)
    {
        if (!conf('visitor-permission-check', true)) {
            return true;
        }

        if (empty($this->permissions) && empty($anthor->permissions)) {
            return true;
        }
        
        $permission=$anthor->permissions;
        list($this_parent, $this_childs)=self::splitIt($this->permissions);
        // 去除父级权限元素
        $permission=array_diff($permission, $this_parent);
        // 去除父级权限的子权限 g.n
        foreach ($permission as $id=>$name) {
            if (strpos($name, '.')) {
                list($p, $c)=preg_split('/\./', $name, 2);
                if (in_array($p, $this_parent)) {
                    unset($permission[$id]);
                }
            }
        }
        // 去除父级权限的子权限 name
        foreach ($this_parent as $parent) {
            if (isset(self::$permissionTable[$parent])) {
                $permission=array_diff($permission, self::$permissionTable[$parent]);
            }
            if (empty($permission)) {
                return true;
            }
        }
        if (count(array_diff($permission, $this_childs))) {
            return false;
        }
        return true;
    }

    /**
     * 检查是否包含单个权限name
     *
     * @param string $name
     * @return boolean
     */
    public function has(string $name)
    {
        list($this_parent, $this_childs)=self::splitIt($this->permissions);
        if ($this->isParent($name)) {
            return is_array($name, $this_parent);
        } elseif (in_array($name, $this_childs)) {
            return true;
        } else {
            foreach ($this_parent as $parent) {
                if ($this->isChild($parent, $name)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function isParent(string $name)
    {
        return in_array($name, array_keys(self::$permissionTable));
    }

    private static function isChild(string $parent, string $child)
    {
        if (strpos($child, '.')) {
            list($p, $c)=explode('.', $child, 2);
            if ($parent==$p) {
                return true;
            }
        }
        if (self::isParent($parent)) {
            return in_array($child, self::$permissionTable[$parent]);
        }
        return false;
    }

    private function splitIt(array $permission)
    {
        $parent=[];
        // 去除父级元素
        foreach ($permission as $index=> $perm) {
            if ($this->isParent($perm)) {
                $parent[]=$perm;
                unset($permission[$index]);
            }
        }

        // 去除父级权限的子权限
        foreach ($parent as $index) {
            if (isset(self::$permissionTable[$index])) {
                $permission=array_diff($permission, self::$permissionTable[$index]);
            }
            if (empty($permission)) {
                break;
            }
        }
        // 父级，子集
        return [$parent,$permission];
    }

    private function filter(array $in)
    {
        return array_filter($in,function($name){
            return in_array($name,Permission::$permissionFilter);
        });
    }

    public function getSystemPermissions()
    {
        return  array_keys(self::$permissionTable);
    }

    public function jsonSerialize()
    {
        return self::toArray();
    }

    public function toArray():array
    {
        list($this_parent, $this_childs)=self::splitIt($this->permissions);
        return array_merge($this_parent, $this_childs);
    }

    public static function readPermissions()
    {
        $modules=app()->getLiveModules();
        $permissions=[];
        foreach ($modules as $module) {
            if (storage()->exist($jsonfile=app()->getModulePath($module).'/resource/config/permissions.json')) {
                $tmp=Json::loadFile($jsonfile);
                $permissions=array_merge($permissions, $tmp);
            }
        }
        foreach ($permissions as $parent=>$child) {
            self::set($parent, array_keys($child['childs']));
        }
        static::$permissionConfig=$permissions;
        return $permissions;
    }

    public static function alias(string $permission):string
    {
        if (static::isParent($permission)) {
            return static::$permissionConfig[$permission]['name'];
        } elseif (strpos($permission, '.')) {
            list($parent, $child) = explode('.', $permission, 2);
            if (static::isParent($parent) && static::isChild($parent, $child)) {
                return static::$permissionConfig[$parent]['childs'][$child];
            }
        }
        return $permission;
    }

    /**
     * 反射读取函数执行权限
     *
     * @param [type] $method 可调用的函数
     * @return void
     */
    public static function createFromFunction($method)
    {
    
        // -[x] authname,groupname
        // -[x] group.authname
        // -[x] group.*
        // -[x] group.[auth1,auth2]
 
        if ($method instanceof \ReflectionMethod || $method instanceof \ReflectionFunction) {
        } elseif (count($method)>1) {
            $method=new ReflectionMethod($method[0], $method[1]);
        } else {
            $method=new ReflectionFunction($method);
        }
        $docs=$method->getDocComment();
        if ($docs && preg_match('/@ACL\s+(.+?)?\s*$/im', $docs, $match)) {
            $acl=null;
            if (isset($match[1])) {
                $permissions=preg_replace_callback('/([^.,]+)\.\[([^.]+)\]/', function ($matchs) {
                    list($all, $parent, $childs) = $matchs;
                    $acls=explode(',', trim($childs, ','));
                    $premStr='';
                    foreach ($acls as $perm) {
                        $premStr.=$parent.'.'.$perm.',';
                    }
                    return $premStr;
                }, $match[1]);
                $acl=explode(',', trim($permissions, ','));
            }
            return new Permission($acl);
        }
        return false;
    }
}
