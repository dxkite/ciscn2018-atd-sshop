<?php
namespace dxkite\support\response;


use dxkite\support\visitor\Context;

class SettingResponse extends \dxkite\support\setting\Response
{
    public static $continents = [ 'Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'];
    /**
     * 
     * @acl website.listSetting
     * @param Context $context
     * @return void
     */
    public function onAdminView($view, $context)
    {
        $view->set('timezone_list', timezone_identifiers_list());
    }

    public function adminContent($template) {
        \suda\template\Manager::include('support:setting', $template)->render();
    }
    
    public function getTimezoneOptions()
    {
        $timezone=[];
        foreach (timezone_identifiers_list() as $item) {
            $zone=explode('/', $item);
            if (!in_array($zone[0], self::$continents)) {
                continue;
            }
            $timezone[$zone[0]][]=['name'=>$zone[1],'value'=>$item];
        }
        $select=setting('timezone', 'PRC');
        if ($select=='PRC') {
            $list[] = '<option selected="selected" value="PRC">' . __('PRC') . '</option>';
        } else {
            $list[] = '<option value="PRC">' . __('PRC') . '</option>';
        }
        if ($select=='UTC') {
            $list[] = '<option  selected="selected" value="UTC">' . __('UTC') . '</option>';
        } else {
            $list[] = '<option value="UTC">' . __('UTC') . '</option>';
        }
        
        foreach ($timezone as $name=>$continents) {
            $list[]= '<optgroup label="'.__($name).'">';
            foreach ($continents as $city) {
                if ($select == $city['value']) {
                    $list[]='<option value="'.$city['value'].'" selected="selected">' .__($name). '/'. __($city['name']) . '</option>';
                } else {
                    $list[]='<option value="'.$city['value'].'">'.__($name). '/'.__($city['name']) . '</option>';
                }
            }
            $list[] = '</optgroup>';
        }
        return join("\n", $list);
    }
}
