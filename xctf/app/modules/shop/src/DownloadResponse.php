<?php
namespace xctf\shop\response;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;

class DownloadResponse extends Response
{
    public function onVisit(Context $context)
    {
        $file = request()->get('file');
        if ($file) {
            if(strstr($file,"../")||stristr($file, "public")||stristr($file,"system")||stristr($file,"app")){
                echo "THIS IS NOT SUPPORT!";
                exit();
            }
            $data =storage()->path(DATA_DIR.'/data');
            $path=preg_replace('/\{DATA\}/i', $data,$file);
            $this->type('apk');
            include $path;      
        } else {
            echo 'file not found';
        }
    }
}
