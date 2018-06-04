<?php
namespace xctf\shop\table;

use suda\archive\Table;

class UserTable extends Table
{
    public function __construct()
    {
        parent::__construct('user');
    }
    
    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('username', 'varchar', 50)->unique()->comment('用户名'),
            $table->field('mail', 'varchar', 50)->unique()->comment('邮箱'),
            $table->field('password', 'varchar', 60)->comment("密码"),
            $table->field('wallet', 'varchar', 60)->comment("钱包"),
            $table->field('integral', 'bigint')->default(500)->comment('积分'),
            $table->field('invite', 'int')->default(0)->comment('邀请人数')
        );
    }

    public function _inputPasswordField($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
