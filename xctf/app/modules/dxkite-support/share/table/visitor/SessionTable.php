<?php
namespace dxkite\support\table\visitor;

use suda\archive\Table;
use dxkite\support\visitor\Permission;

class SessionTable extends Table
{
    public function __construct()
    {
        parent::__construct('visitor_session');
    }
    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('grantee','bigint', 20)->key()->comment("授权者"),
            $table->field('token','varchar',32)->comment("验证令牌"),
            $table->field('expire','int',11)->comment("过期时间"),
            $table->field('time', 'int',11)->comment("会话时间"),
            $table->field('ip','varchar',32)->comment("会话IP")
        );
    }
}
