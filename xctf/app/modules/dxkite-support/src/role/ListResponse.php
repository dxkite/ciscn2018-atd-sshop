<?php
namespace dxkite\support\response\role;

use dxkite\support\visitor\Context;
use dxkite\support\template\Manager;
use dxkite\support\table\setting\SettingTable;

class ListResponse extends \dxkite\support\setting\Response
{
    /**
     * 列出权限
     *
     * @acl role.[list,delete]
     * @param [type] $view
     * @param [type] $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        if (request()->get()->delete) {
            table('role')->deleteByPrimaryKey(request()->get()->delete);
            $this->refresh(true);
            return;
        }
        $page=request()->get()->page(1);
        $view->set('list', table('role')->list($page, 10));
        $max= table('role')->count();
        $view->set('page.max', ceil($max/10));
        $view->set('page.router', 'support:admin_role_list');
        $view->set('page.now', $page);
        $auths=$context->getVisitor()->getPermission()->readPermissions();
        $view->set('auths', $auths);
    }

    public function adminContent($template)
    {
        \suda\template\Manager::include('support:role/list', $template)->render();
    }
}
