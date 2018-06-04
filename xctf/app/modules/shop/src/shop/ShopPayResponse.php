<?php
namespace xctf\shop\response\shop;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\table\CommodityTable;
use xctf\shop\table\UserTable;

class ShopPayResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        $userTable =  new UserTable;
        $price = request()->post('price');
        $user = $userTable ->getByPrimaryKey(get_user_id());
        if ( $user['integral'] - $price  >=  0) {
            $user['integral'] -= $price;
            $userTable -> updateByPrimaryKey(get_user_id(), [
                'integral'=>$user['integral']
            ]);
            return $this->page('pay')->set('success', 1)->render();
        }
        return $this->page('pay')->set('danger', 1)->render();
    }
}
