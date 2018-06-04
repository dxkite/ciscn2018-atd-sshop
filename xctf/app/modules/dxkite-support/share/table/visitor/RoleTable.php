<?php
namespace dxkite\support\table\visitor;

use suda\archive\Table;
use dxkite\support\visitor\Permission;

class RoleTable extends Table
{
    public function __construct()
    {
        parent::__construct('visitor_role');
    }
    
    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('name', 'varchar', 255)->unique()->comment("角色名"),
            $table->field('permission', 'text')->comment("权限控制对象"),
            $table->field('sort', 'int', 11)->key()->default(0)->comment("排序索引")
        );
    }

    public function _inputPermissionField($permission)
    {
        if (!($permission instanceof Permission)) {
            $permission=new Permission($permission);
        }
        return serialize($permission);
    }

    public function _outputPermissionField($permission){
        return unserialize($permission);
    }
}
