<?php
namespace dxkite\support\response\role;

use dxkite\support\visitor\Context;
use dxkite\support\template\Manager;
use dxkite\support\table\setting\SettingTable;
use dxkite\support\visitor\GrantManager;
use dxkite\support\visitor\Permission;

class AddResponse extends \dxkite\support\setting\Response
{
    /**
     * 添加权限
     *
     * @acl role.create
     * @param [type] $view
     * @param [type] $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        if (request()->hasPost()) {
            $id=(new GrantManager)->createRole(request()->post()->name, new Permission(array_keys(request()->post()->auths([]))));
            if ($id) {
                return $this->go(u('support:admin_role_list'));
            } else {
                $page->set('invaildName', true);
            }
        }
        $auths=$context->getVisitor()->getPermission()->readPermissions();
        $view->set('auths', $auths);
    }

    public function adminContent($template)
    {
        \suda\template\Manager::include('support:role/add', $template)->render();
    }
}
