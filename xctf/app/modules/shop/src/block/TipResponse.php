<?php
namespace xctf\shop\response\block;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;

class TipResponse extends Response
{
    public function onVisit(Context $context)
    {
        $this->page('block/tip')->render();
    }
}
