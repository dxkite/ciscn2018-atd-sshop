<?php
namespace xctf\shop\response\block;

use dxkite\support\visitor\response\VisitorResponse;
use dxkite\support\visitor\Context;
use xctf\shop\Block;
use xctf\shop\table\UserTable;

class DigResponse extends VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        $token = request()->get('hash');
        $user = (new UserTable) ->getByPrimaryKey(get_user_id());
        $view = $this->page('block/show');
        if ($user['wallet'] == $token) {
            if ($block= Block::dig(get_user_id(), $user['invite']+1)) {
                $view->set('block', $block);
            }
            $view->set('wallet', $token);
        }
        $view->render();
    }
}
