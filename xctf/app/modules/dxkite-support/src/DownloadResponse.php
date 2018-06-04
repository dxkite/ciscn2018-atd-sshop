<?php
namespace dxkite\support\response;

use dxkite\support\visitor\response\Response;
use dxkite\support\visitor\Context;
use  dxkite\support\database\DbHelper;
use suda\tool\ZipHelper;
use dxkite\support\template\Manager;

class DownloadResponse extends Response
{
    public function onVisit(Context $context)
    {
        $name=request()->get('name');
        if ($name) {
            if (isset(request()->get()->template)) {
                $ouptut=TEMP_DIR.'/export-'.setting('template');
                Manager::exportTemplate($ouptut);
                $tempFile=tempnam(sys_get_temp_dir(),'tpl-');
                if(ZipHelper::zip($ouptut,$tempFile)){
                    $this->file($tempFile,setting('template').'-'.setting('template-id'),'zip');
                    storage()->delete($ouptut);
                }
            }
            elseif ($path=DbHelper::backupPath($name)) {
                $tempFile=tempnam(sys_get_temp_dir(),'bk-');
                if(ZipHelper::zip($path,$tempFile)){
                    $this->file($tempFile,$name,'zip');
                }
            }
        }
        hook()->exec('system:404');
    }
}
