<?php

namespace xctf\shop;

use xctf\shop\table\CommodityTable;

use suda\archive\SQLQuery;

class Init
{
    public static function initSystem()
    {
        $install_lock = DATA_DIR.'/install/install_'.substr(md5(__FILE__), 0, 6).'.lock';
        storage()->mkdirs(dirname($install_lock));
        if (storage()->exist($install_lock)) {
            return;
        } else {
            file_put_contents($install_lock, 'time='.microtime(true));
        }
        (new SQLQuery('DROP DATABASE IF EXISTS '.conf('database.name')))->exec();
        (new SQLQuery('CREATE DATABASE '.conf('database.name')))->exec();
        (new SQLQuery('USE '.conf('database.name')))->exec();
        $table = new CommodityTable;
        $table->insert([
            'name' => 'XiaoMi X',
            'desc' => '这个是Chacker可以购买的商品,FLAG 专用商品',
            'amount'=>1000,
            'price'=> 100
        ]);
        $table->insert([
            'name' => 'IPhone X',
            'desc' => '这个是Chacker可以购买的商品,FLAG 专用商品',
            'amount'=>1000,
            'price'=> 100
        ]);
        $table->insert([
            'name' => 'Huawei Cloud WAF',
            'desc' => '这个是Chacker可以购买的商品,FLAG 专用商品',
            'amount'=>1000,
            'price'=> 0.01
        ]);
        $id=$table->insert([
            'name' => 'flag{6Zev5YWz5o+Q56S6}',
            'desc' => 'FLAG IS NOT IN HERE : 5oOz6I635b6XZmxhZyzlsLHor7fotK3kubDml7bmipPljIXvvIzms6jmhI/mt7vliqDotK3nianovablk6Z+',
            'amount'=>2,
            'price'=> conf('targetPrice', 9999)
        ]);
        setting_set('Flag_Commodity', $id);
        setting_set('Buy_Commodity', false);
    }
}
