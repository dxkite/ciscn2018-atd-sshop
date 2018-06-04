<?php
namespace dxkite\support\response;

use dxkite\support\visitor\Context;
use dxkite\support\template\Manager;
use dxkite\support\table\setting\SettingTable;

class EmailResponse extends \dxkite\support\setting\Response
{
    /**
     * 设置邮件
     * 
     * @acl website.[setEmail,viewEmail]
     * 
     * @param [type] $view
     * @param [type] $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        if (setting('smtp')) {
            $smtp = setting('smtp');
            $smtp['passwd'] = '';
            $view->set('smtp', $smtp);
        }
        if (request()->hasPost() && request()->post('smtp')) {
            setting_set('smtp', request()->post('smtp'));
            $this->go(u());
        }
    }

    public function adminContent($template)
    {
        \suda\template\Manager::include('support:set-email', $template)->render();
    }
}
