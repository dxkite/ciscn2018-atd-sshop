<?php
namespace dxkite\support\response;


use dxkite\support\visitor\Context;
use dxkite\support\template\Manager;
use dxkite\support\table\setting\SettingTable;

class TemplateResponse extends \dxkite\support\setting\Response
{
    /**
     * 查看模板设置
     * @acl template.list
     * @param [type] $view
     * @param [type] $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        $request=$context->getRequest();
        if ($request->get()->template) {
            $themeId= $request->get()->template('default');
            $templates=Manager::instance()->getTemplate();
            setting_set('template-id', $themeId);
            setting_set('template',$templates[$themeId]->name);
            $this->go(u(static::$name));
        }

        elseif ($request->get()->delete) {
            Manager::instance()->delete($request->get()->delete);
            $this->go(u(static::$name));
        }
        $list=Manager::instance()->getTemplateList();
        $view->set('list', $list);
        $view->set('modules', json_encode(app()->getModules()));
    }

    public function adminContent($template) {
        \suda\template\Manager::include('support:template', $template)->render();
    }
}
