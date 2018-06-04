<?php
namespace dxkite\support\table\visitor;

use suda\archive\Table;
use dxkite\support\visitor\Permission;

class GrantTable extends Table
{
    public function __construct()
    {
        parent::__construct('visitor_grant');
    }

    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('grant', 'bigint', 20)->key()->unsigned()->foreign((new RoleTable)->getCreator()->getField('id')),
            $table->field('investor','bigint', 20)->key()->comment("授权者"),
            $table->field('grantee','bigint', 20)->key()->comment("授予者"),
            $table->field('time', 'int', 11)->key()->comment("授予时间")
        );
    }
}
