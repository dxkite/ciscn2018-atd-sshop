<?php
namespace xctf\shop\response\shop;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;
use xctf\shop\table\CommodityTable;

class ShopDetailResponse extends Response
{
    public function onVisit(Context $context)
    {
        $id=intval(request()->get()->id(0))??0;
        $table = new CommodityTable;
        $commoditys = $table->getByPrimaryKey($id);
        $view = $this->page('info');
        $view->set('commodity', $commoditys?:[]);
        $view->render();
    }
}
