<?php
namespace dxkite\support\response\role;

use dxkite\support\visitor\Context;
use dxkite\support\template\Manager;
use dxkite\support\table\setting\SettingTable;
use dxkite\support\visitor\GrantManager;
use dxkite\support\visitor\Permission;

class EditResponse extends \dxkite\support\setting\Response
{
    /**
     * 编辑权限
     *
     * @acl role.edit
     * @param [type] $view
     * @param [type] $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        $id=request()->get()->id;
        if ($id){
            if (request()->hasPost()) {
                (new GrantManager)->editRole($id,request()->post('name'),new Permission(array_keys(request()->post()->auths([]))));
            }
            $info=table('role')->getByPrimaryKey($id);
            $view->set('name', $info['name']);
            $view->set('title',__('角色_%s', $info['name']));
            $view->set('permission', $info['permission']);
            $auths=$context->getVisitor()->getPermission()->readPermissions();
            $view->set('auths', $auths);
        }
        else{
            $view->set('invaildId',true);
        }
    }
    public function adminContent($template)
    {
        \suda\template\Manager::include('support:role/edit', $template)->render();
    }
}
