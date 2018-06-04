<?php
namespace xctf\shop\table;

use suda\archive\Table;

class CommodityTable extends Table
{
    public function __construct()
    {
        parent::__construct('commoditys');
    }

    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('name', 'varchar', 200)->unique()->null(false)->comment('商品名'),
            $table->field('desc', 'varchar', 500)->default(__('no description'))->comment('描述'),
            $table->field('amount', 'int')->default(10)->comment("数量"),
            $table->field('price', 'int')->null(false)->comment('价格')
        );
    }

    public function _inputPriceField($value)
    {
        return $value*100;
    }
    
    public function _outputPriceField($value)
    {
        return $value/100;
    }
}
