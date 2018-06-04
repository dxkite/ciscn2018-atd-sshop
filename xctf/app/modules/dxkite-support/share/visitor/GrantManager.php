<?php

namespace dxkite\support\visitor;

use dxkite\support\proxy\ProxyObject;

class GrantManager extends ProxyObject
{
    /**
     * 创建角色
     *
     * @acl role.create
     * @param string $name
     * @param Permission $permission
     * @param integer $sort
     * @return void
     */
    public function createRole(string $name, Permission $permission, int $sort=0)
    {
        if (table('role')->select('id', ['name'=>$name])->fetch()) {
            return false;
        }
        return table('role')->insert(['name'=>$name,'permission'=>$permission,'sort'=>$sort]);
    }

    /**
     * 授权
     * 
     * @acl role.grant
     * @param integer $id 角色ID
     * @param integer $grantee ID
     * @return void
     */
    public function grant(int $id, int $grantee)
    {
        if (table('grant')->select('id', ['grantee'=>$grantee,'id'=>$id])->fetch()) {
            return true;
        }
        return table('grant')->insert(['investor'=>$this->getUserId(),'grantee'=>$grantee,'time'=>time(),'grant'=>$id]);
    }
    
    /**
     * 编辑角色
     *
     * @acl role.edit
     * @param integer $id
     * @param string $name
     * @param Permission $permisson
     * @param integer $sort
     * @return void
     */
    public function editRole(int $id, string $name, Permission $permisson, int $sort=0)
    {
        $id=table('role')->updateByPrimaryKey($id, [
            'name'=>$name,
            'permission'=>  $permisson,
            'sort'=>$sort,
        ]);
        return $id;
    }

    /**
     * 收回权限
     * 
     * @acl role.revoke
     * @param integer $id
     * @param integer $grantee
     * @return void
     */
    public function revoke(int $id, int $grantee)
    {
        if ($get=table('grant')->select('id', ['grantee'=>$grantee,'id'=>$id])->fetch()) {
            return  table('grant')->deleteByPrimaryKey($get['id']);
        }
        return false;
    }
 
    /**
     * 收回某个用户的全部权限
     * 
     * @acl role.revoke
     *
     * @param integer $grantee
     * @return void
     */
    public function revokeAll(int $grantee)
    {
        return table('grant')->delete(['grantee'=>$grantee]);
    }
    
    /**
     * 列出角色列表
     * @acl role.list
     * @param integer $page
     * @param integer $rows
     * @return void
     */
    public function listRole(int $page=null, int $rows=10)
    {
        $role= table('role')->setWants(['id','name','permission']);
        if (is_null($page)) {
            $list=$role->list();
        } else {
            $list=$role->list($page, $rows);
        }
        return $list;
    }
}
