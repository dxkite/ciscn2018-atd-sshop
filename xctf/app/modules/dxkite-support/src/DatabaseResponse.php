<?php
namespace dxkite\support\response;

use dxkite\support\visitor\Context;
use suda\core\Query;
use  dxkite\support\database\DbHelper;

class DatabaseResponse extends \dxkite\support\setting\Response
{
    /**
     * 备份数据库
     *
     * @param Context $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        if ($delete=request()->get()->delete) {
            DbHelper::deleteBackup($delete);
            $this->forward();
            return;
        }
        $list=DbHelper::list();
        if ($list) {
            $view->set('list', $list);
        }
    }

    public function adminContent($template)
    {
        \suda\template\Manager::include('support:database', $template)->render();
    }
}
