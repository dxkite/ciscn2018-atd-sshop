<?php
namespace xctf\shop\response\shop;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\table\CommodityTable;
use xctf\shop\table\UserTable;

class ShopCarResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        $userTable =  new UserTable;
        $commodityTable =  new CommodityTable;
        $id = session()->get('commodity_id');
        $commodity = $commodityTable->getByPrimaryKey($id);
        $price =$commodity['price'];
        if (request()->method()==='GET') {
            $view = $this->page('shopcar');
            if ($commodity) {
                $view->set('commodity', $commodity);
            }
            return $view->render();
        } else {
            $user = $userTable ->getByPrimaryKey(get_user_id());
            if ($commodity && $user['integral'] - $price  >= 0) {
                $user['integral'] -= $price;
                $userTable -> updateByPrimaryKey(get_user_id(), [
                    'integral'=>$user['integral']
                ]);
                $commodityTable -> updateByPrimaryKey($id, [
                    'amount'=>$commodity['amount'] - 1
                ]);
                if ($commodity['id'] == setting('Flag_Commodity')){
                    cookie()->set('flag_tip','6K+35LiL6L29YXBw');
                    setting_set('Buy_Commodity',true);
                }
                session()->delete('commodity_id');
                return $this->page('shopcar')->set('success', 1)->render();
            }
            return $this->page('shopcar')->set('danger', 1)->render();
        }
        $this->go(u('shopCar'));
    }
}
