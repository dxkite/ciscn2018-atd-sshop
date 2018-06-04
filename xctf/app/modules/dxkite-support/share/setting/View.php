<?php
namespace dxkite\support\setting;

use suda\core\Router;
use suda\core\Application;
use suda\tool\Json;
use suda\core\Storage;
use suda\template\compiler\suda\Compiler;
use dxkite\support\visitor\Context;
use dxkite\support\visitor\Permission;
use suda\core\route\Mapping;

class View
{
    private static $namespace='/setting';
    private static $adminsidebar=[];
    private static $adminSidebarTree=null;
    private static $childsidebar=null;
    
    public static function addAdminSidebar(Router $router)
    {
        debug()->info('add admin sidebar');
        $modules=app()->getLiveModules();
        foreach ($modules as $module) {
            self::addSidebarRouterConfig($module, $router);
        }
    }

    public static function hook(Compiler $compiler)
    {
        $compiler->addCommand('isChild', function ($expr) {
            return '<?php echo '.__CLASS__ ."::isChild{$expr}; ?>";
        });
        $compiler->addCommand('time', function ($expr) {
            return '<?php echo '.__CLASS__ ."::time{$expr}; ?>";
        });
    }

    public static function time(int $time)
    {
        $text = '';
        $time = $time === null || $time > time() ? time() : intval($time);
        $t = time() - $time;
        $y = date('Y', $time)-date('Y', time());
        switch ($t) {
         case $t < 20:
           $text = __('刚刚');
           break;
        case $t < 60:
          $text = __('%d秒前', $t);
          break;
        case $t < 60 * 60:
          $text = __('%d分钟前', floor($t / 60));
          break;
        case $t < 60 * 60 * 24:
          $text = __('%d小时前', floor($t / (60 * 60)));
          break;
        case $t < 60 * 60 * 24 * 3:
          $text = floor($time/(60*60*24)) ==1 ? date(__('昨天 H:i'), $time) : date(__('前天 H:i'), $time);
          break;
        case $t < 60 * 60 * 24 * 30:
          $text = date(__('m月d日 H:i'), $time);
          break;
        case $t < 60 * 60 * 24 * 365&&$y==0:
          $text = date(__('m月d日'), $time);
          break;
        default:
          $text = date(__('Y年m月d日 H:i:s'), $time);
          break;
        }
        return $text;
    }

    public static function isChild(string $parent, string $child, string $true, string $false='')
    {
        $parent=Router::getInstance()->getRouterFullName($parent);
        $child=Router::getInstance()->getRouterFullName($child);
        if (isset(self::$childsidebar[$parent])) {
            return in_array($child, self::$childsidebar[$parent])?$true:$false;
        }
        return $false;
    }

    /**
     * 压入管理侧边栏
     *
     * @param [type] $template
     * @return void
     */
    public static function renderAdminSidebar($template)
    {
        // 排序侧边栏
        $adminsidebar=self::adminSidebarSort(self::$adminSidebarTree);
        // 压入模板内存
        $template->assign(['admin'=>['sidebar'=>$adminsidebar]]);
    }

    private static function addSidebarRouterConfig(string $module, Router $router)
    {
        if (storage()->exist($path=app()->getModulePath($module).'/resource/config/setting/router.json')) {
            $routers=Json::loadFile($path);
            foreach ($routers as $name=>$router_info) {
                $fix=preg_replace('/:(.+)$/', '', $module);
                $autoPrefix=$router_info['anti-fix']??false;
                $moduleurl=($autoPrefix)?self::$namespace:self::$namespace.'/'.$fix;
                if (isset($router_info['acl']) && !static::checkMe($router_info['acl'])) {
                    continue;
                }
                $mapping=Mapping::createFromRouteArray(Mapping::ROLE_SIMPLE, $module, $name, $router_info);
                $mapping->setUrl(rtrim($moduleurl.$mapping->getUrl(), '/'));
                $mapping->setDynamic();
                $mapping->build();
                $router->addMapping($mapping);
            }
        }
        self::loadSidebarConfig();
    }

    private static function loadSidebarConfig()
    {
        if (count(self::$adminsidebar) ==0) {
            $modules=app()->getLiveModules();
            foreach ($modules as $module) {
                self::addSidebarConfig($module);
            }
            if (is_null(self::$adminSidebarTree)) {
                self::$adminSidebarTree= self::transAdminSidebarTree(self::$adminsidebar);
            }
        }
    }
    
    private static function addSidebarConfig(string $module)
    {
        if (storage()->exist($jsonfile=app()->getModulePath($module).'/resource/config/setting/sidebar.json')) {
            $configs=Json::loadFile($jsonfile);
            self::$adminsidebar=array_merge(self::$adminsidebar, $configs);
        }
    }

    // 管理侧边栏格式调整
    private static function adminSidebarTree()
    {
        self::loadSidebarConfig();
        $sidebar=[];
        // 调整侧边栏格式
        foreach (self::$adminsidebar as $name=>$adminsidebar) {
            $id=count($sidebar);
            // 验证权限
            if (isset($sidebar['acl']) && !static::checkMe($sidebar['acl'])) {
                continue;
            }
            $parent=Router::getInstance()->getRouterFullName($name);
            $sidebar[$id]['text']=__($adminsidebar['name']);
            $sidebar[$id]['href']=u($parent, $adminsidebar['args']??[]);
            $sidebar[$id]['id']=$parent;
            $sidebar[$id]['sort']=$adminsidebar['sort']??0;
            $pMapping=Router::getInstance()->getRouter($parent);
            $pMapping->setParam([
                'config'=>$sidebar[$id],
                'isChild'=>false,
            ]);
            Router::getInstance()->refreshMapping($pMapping);
            // 二级菜单
            if (isset($adminsidebar['child']) && is_array($adminsidebar['child'])) {
                foreach ($adminsidebar['child'] as $name=>$child) {
                    if (isset($child['acl']) && !static::checkMe($child['acl'])) {
                        continue;
                    }
                    $cid=count($sidebar[$id]['child']??[]);
                    self::$childsidebar[$parent][]=Router::getInstance()->getRouterFullName($name);
                    $sidebar[$id]['child'][$cid]['text']=__($child['name']);
                    $sidebar[$id]['child'][$cid]['href']=u($name, $child['args']??[]);
                    $sidebar[$id]['child'][$cid]['id']=$name;
                    $sidebar[$id]['child'][$cid]['sort']=$child['sort']??0;
                    $cMapping=Router::getInstance()->getRouter(Router::getInstance()->getRouterFullName($name));
                    $cMapping->setParam([
                        'config'=> $sidebar[$id]['child'][$cid],
                        'isChild'=>true,
                        'parent'=>$sidebar[$id],
                    ]);
                    Router::getInstance()->refreshMapping($cMapping);
                }
            }
        }
    }

    // 管理侧边栏格式调整
    private static function transAdminSidebarTree(array $input)
    {
        $sidebar=[];
        // 调整侧边栏格式
        foreach ($input as $name=>$adminsidebar) {
            $id=count($sidebar);
            // 验证权限
            if (isset($sidebar['acl']) && !static::checkMe($sidebar['acl'])) {
                continue;
            }
            $parent=Router::getInstance()->getRouterFullName($name);
            $sidebar[$id]['text']=__($adminsidebar['name']);
            $sidebar[$id]['href']=u($parent, $adminsidebar['args']??[]);
            $sidebar[$id]['id']=$parent;
            $sidebar[$id]['sort']=$adminsidebar['sort']??0;
            // 二级菜单
            if (isset($adminsidebar['child']) && is_array($adminsidebar['child'])) {
                foreach ($adminsidebar['child'] as $name=>$child) {
                    if (isset($child['acl']) && !static::checkMe($child['acl'])) {
                        continue;
                    }
                    self::$childsidebar[$parent][]=Router::getInstance()->getRouterFullName($name);
                    $cid=count($sidebar[$id]['child']??[]);
                    $sidebar[$id]['child'][$cid]['text']=__($child['name']);
                    $sidebar[$id]['child'][$cid]['href']=u($name, $child['args']??[]);
                    $sidebar[$id]['child'][$cid]['id']=$name;
                    $sidebar[$id]['child'][$cid]['sort']=$child['sort']??0;
                }
            }
        }
        return $sidebar;
    }
 
    /**
     * 按索引升序排列
     *
     * @param [type] $list
     * @return void
     */
    private static function adminSidebarSort($list)
    {
        $list=self::sort($list);
        foreach ($list as $id=>$item) {
            if (isset($item['child'])) {
                $list[$id]['child']=self::sort($item['child']);
            }
        }
        return $list;
    }
 
    private static function sort(array $array)
    {
        uasort($array, function ($a, $b) {
            if (isset($a['sort']) && isset($b['sort'])) {
                return $a['sort']-$b['sort'];
            }
            return 0;
        });
        return $array;
    }

    protected static function checkMe(array  $permission)
    {
        return Context::getInstance()->getVisitor()->surpass(new Permission($permission));
    }
}
