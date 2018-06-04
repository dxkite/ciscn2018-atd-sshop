<?php
namespace dxkite\support\setting;

use dxkite\support\visitor\Context;
use suda\core\Query;

abstract class Response extends \dxkite\support\visitor\response\VisitorResponse
{
    public function onUserVisit(Context $context)
    {
        if ($context->getVisitor()->canAccess([$this,'onAdminView'])) {
            $page=$this->page('support:setting-view');
            $page->set('version.server', $_SERVER["SERVER_SOFTWARE"]);
            $page->set('version.php', PHP_VERSION);
            $page->set('version.suda', SUDA_VERSION);
            $mapping=\suda\core\route\Mapping::current();
            if ($param=$mapping->getParam()) {
                if ($param['isChild']) {
                    $page->set('menuName', $param['parent']['text']);
                    $page->set('title', $param['parent']['text'] . ' - ' .$param['config']['text']);
                } else {
                    $page->set('menuName', $param['config']['text']);
                    $page->set('title', $param['config']['text']);
                }
            }
            if ($this->onAdminView($page, $context) !== false) {
                $page->render();
            }
        } else {
            $this->onDeny($context);
        }
    }

    abstract public function onAdminView($page, $context);
    abstract public function adminContent($template);
    public function onDeny(Context $context)
    {
        $this->page('support:deny')->render();
    }
}
