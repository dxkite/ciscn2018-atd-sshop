<?php
namespace xctf\shop\response\shop;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;
use xctf\shop\table\CommodityTable;

class ShopListResponse extends Response
{
    public function onVisit(Context $context)
    {
        $page=intval(request()->get()->page(1))??1;
        $table = new CommodityTable;
        
        // $table->insert([
        //     'name'=>'XCDX%FA-'.substr(time(),-3),
        //     'desc'=>'ATD计算机协会用 CTF ATD战队',
        //     'price'=>100,
        //     'amount'=>100,
        // ]);
        //   $table->insert([
        //     'name'=>'XCDX%FA-'.substr(time(),-3),
        //     'desc'=>'ATD计算机协会用 CTF ATD战队',
        //     'price'=>100,
        //     'amount'=>100,
        // ]);

        $commoditys = $table->order('price',$table::ORDER_DESC)->listWhere('amount > 0',[],$page,conf('module.limit',10));
        $view = $this->page('index');
        $view->set('commoditys',$commoditys?:[]);
        $view->set('preview',$page-1);
        $view->set('next',$page+1);
        $view->set('limit',conf('module.limit',10));
      
        $view->render();
    }
}
