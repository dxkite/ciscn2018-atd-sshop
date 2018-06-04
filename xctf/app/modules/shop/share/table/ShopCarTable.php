<?php
namespace xctf\shop\table;

use suda\archive\Table;

class ShopCarTable extends Table
{
    public function __construct()
    {
        parent::__construct('shopcar');
    }
    
    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto()
        );
    }
}
