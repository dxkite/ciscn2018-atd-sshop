<?php
namespace xctf\shop\response\block;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;
use xctf\shop\Block;
use xctf\shop\table\UserTable;

class SearchResponse extends Response
{
    public function onVisit(Context $context)
    {
        $token = request()->get('hash', false);
        if ($token) {
            if ($get=Block::check($token)) {
                $user = (new UserTable) ->getByPrimaryKey($get['user']);
                $this->page('block/info')->set('block', $get)->set('wallet', $user['wallet'])->render();
            } else {
                $this->page('block/error')->render();
            }
        } else {
            $this->page('block/search')->render();
        }
    }
}
