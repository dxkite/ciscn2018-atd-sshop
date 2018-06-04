<?php
namespace xctf\shop\response\shop;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\table\CommodityTable;
use xctf\shop\table\UserTable;

class SecKillResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        if (request()->method()==='GET') {
            return $this->page('seckill')->render();
        } else {
            $id = intval(request()->post('id'));
            $commodityTable = new CommodityTable;
            $commodity = $commodityTable ->getByPrimaryKey($id);
            if ($commodity) {
                $commodityTable -> updateByPrimaryKey($id, [
                    'amount'=>$commodity['amount'] - 1
                ]);
                return $this->page('seckill')->set('success', 1)->render();
            }
            return $this->page('seckill')->set('danger', 1)->render();
        }
    }
}
