<?php
namespace dxkite\support\template;

use suda\tool\Json;

/**
 * 模板信息文件
 */
class Template
{
    public $uniqid;
    public $name;
    public $icon;
    public $author;
    public $authorLink;
    public $version;
    public $discription;
    public $modules;
    public $license;
    public $path;
    public $config;
    public $import;
    public $listener;
    public $value;
    public $valueNamespace;
    public $require;
    public $data;

    public function __construct(string $path)
    {
        $config=Json::parseFile($path);
        $base=dirname($path);
        $this->path=$base;
        $this->name=$config['name']??basename($base);
        $this->uniqid=basename($base);
        if (storage()->exist($icon=$base.'/'.$config['icon'])) {
            $this->icon=$icon;
        }
        $this->author=$config['author']??'??';
        $this->authorLink=$config['authorLink']??'#';
        $this->version=$config['version']??'-';
        $this->discription=$config['discription']??'-';
        if (storage()->exist($license=$base.'/'.$config['license'])) {
            $this->license = $license;
        } else {
            $this->license=$config['license']??'-';
        }
        $base=isset($config['root'])?$base.'/'.$config['root']:$base;
        foreach (($config['modules']??[]) as $module=>$module_dir) {
            if (is_dir($tpl=$base.'/'.$module_dir)) {
                $this->modules[$module]=$tpl;
            }
        }
        $this->config=$config;
        $this->import=$config['import']??[];
        $this->listener=$config['listener']??[];
        $this->value=$config['value']??[];
        $this->valueNamespace= $config['valueNamespace']??'value';
        $this->require=$config['require']??[];
        $this->data=$config['data']??'data';
    }
}
