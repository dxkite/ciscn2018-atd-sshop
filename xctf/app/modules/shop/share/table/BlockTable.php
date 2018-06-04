<?php
namespace xctf\shop\table;

use suda\archive\Table;

class BlockTable extends Table
{
    public function __construct()
    {
        parent::__construct('block_chain');
    }

    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('hash', 'varchar', 200)->unique()->null(false)->comment('计算出来的值'),
            $table->field('previous_hash', 'varchar', 200)->comment('上一个Hash'),
            $table->field('user', 'bigint', 20)->comment('所属用户'),
            $table->field('nonce', 'varchar', 200)->unique()->null(false)->comment('Nonce'),
            $table->field('time', 'int')->comment("时间")
        );
    }
}
