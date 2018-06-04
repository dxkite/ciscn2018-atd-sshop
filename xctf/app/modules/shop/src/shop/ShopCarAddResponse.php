<?php
namespace xctf\shop\response\shop;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\table\CommodityTable;
use xctf\shop\table\UserTable;

class ShopCarAddResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        $id = request()->post('id');
        session()->set('commodity_id', $id);
        debug()->warning('shopcar commodity_id',$id);
        $this->go(u('shopCar'));
    }
}
