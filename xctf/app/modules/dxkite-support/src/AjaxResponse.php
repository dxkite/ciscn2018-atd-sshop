<?php
namespace dxkite\support\response;


use cn\atd3\Setting;
use dxkite\support\file\File;
use dxkite\support\template\Manager;

class AjaxResponse extends \dxkite\support\visitor\response\CallableResponse
{

    /**
     * 设置
     * @acl edit_setting
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setting(string $name, string $value)
    {
        return setting_set($name, $value);
    }

    /**
     * @acl edit_setting
     * 上传模板
     * 
     * @param File $zip
     * @return void
     */
    public function uploadTheme(File $zip)
    {
        return Manager::instance()->upload($zip->getPath(), $zip->getName());
    }

    /**
     * @acl edit_setting
     * 刷新资源
     *
     * @param string $module
     * @return void
     */
    public function refreshModuleTemplate(string $module)
    {
        return current(init_resource([$module]));
    }
}
