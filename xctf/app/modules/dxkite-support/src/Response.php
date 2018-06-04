<?php
namespace dxkite\support\response;

use dxkite\support\visitor\Context;
use suda\core\Query;

class Response extends \dxkite\support\setting\Response
{
    /**
     * 列出网站信息
     * 
     * @acl website.info
     * 
     * @param Context $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        $view->set('version.server', $_SERVER["SERVER_SOFTWARE"]);
        $view->set('version.php', PHP_VERSION);
        $view->set('version.mysql', self::getMySQLVersion());
        $view->set('version.gd', self::getGDVersion());
        $view->set('version.suda', SUDA_VERSION);
        $view->set('upload', self::getFileupload());
    }

    public function adminContent($template) {
        \suda\template\Manager::include('support:index', $template)->render();
    }

    public function getFileupload()
    {
        return ini_get("file_uploads") ? ini_get("upload_max_filesize"):__('不支持文件上传');
    }
    
    public function getMySQLVersion()
    {
        return (new Query('select version() as version'))->fetch()['version']??'unkown';
    }

    public function getGDVersion()
    {
        if (function_exists("gd_info")) {
            $gd = gd_info();
            $gdinfo = $gd['GD Version'];
        } else {
            $gdinfo = __('未知');
        }
        return $gdinfo;
    }
}
