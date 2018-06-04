<?php
namespace xctf\shop\response;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;


class CaptchaResponse extends Response
{
    public function onVisit(Context $context)
    {
        $file =session()->get('questionFile');
        $this->type('jpg');
        $img = imagecreatefromjpeg($file);
        imagejpeg($img);
        imagedestroy($img);
    }
}
