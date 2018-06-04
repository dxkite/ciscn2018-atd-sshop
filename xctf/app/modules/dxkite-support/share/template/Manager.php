<?php
namespace dxkite\support\template;

use suda\tool\ZipHelper;
use suda\template\Manager as SudaTemplateManager;
use suda\core\Autoloader;
use suda\core\{Storage};

/**
 * 模板管理类
 * 管理上传的模板
 */
class Manager
{
    protected $template;
    protected $base;

    protected static $instance;
    const TEMPLATE_DIRNAME='template';
    protected static $current=null;

    protected function __construct()
    {
        $this->base=DATA_DIR.'/'.self::TEMPLATE_DIRNAME;
        $this->template=[];
        $this->loadTemplates();
    }

    public static function themeChange(string $uniqid)
    {
        if (isset(self::instance()->template[$uniqid])) {
            self::$current=$uniqid;
            $info=self::instance()->template[$uniqid];
            if (is_array($info->modules)) {
                foreach ($info->modules as $module=>$path) {
                    SudaTemplateManager::addTemplateSource($module, $path);
                }
            }
            $root= $info->path;
            if (is_array($info->import)) {
                foreach ($info->import as $namespace=>$path) {
                    if (Storage::isDir($dirPath=$root.DIRECTORY_SEPARATOR.$path)) {
                        Autoloader::addIncludePath($dirPath, $namespace);
                    } elseif (Storage::isFile($importPath=$root.DIRECTORY_SEPARATOR.$path)) {
                        Autoloader::import($importPath);
                    }
                }
            }
            if (is_array($info->value)) {
                config()->set($info->valueNamespace, $info->value);
            }
            if (is_array($info->listener)) {
                hook()->load($info->listener);
            }
            if (is_array($info->require)) {
                app()->checkModuleRequire(__('template %s', $info->name), $info->require);
            }
        }
    }

    public static function compilerLoad()
    {
        if ($templateID=setting('template-id')) {
            self::themeChange($templateID);
        }
    }

    public function loadTemplates()
    {
        $dirs=storage()->readDirs($this->base);
        foreach ($dirs as $dir) {
            if (storage()->exist($this->base.'/'.$dir.'/config.json')) {
                $template=new Template($this->base.'/'.$dir.'/config.json');
                $this->template[$template->uniqid]=$template;
            } else {
                storage()->delete($this->base.'/'.$dir);
            }
        }
    }

    public function getTemplateList()
    {
        $list=[];
        foreach ($this->template as $info) {
            if (!is_null($info->icon)) {
                $iconData=storage()->get($info->icon);
                $mime=mime(pathinfo($info->icon, PATHINFO_EXTENSION));
                $info->icon='data:'.$mime.';base64,'.base64_encode($iconData);
            }
            if ($info->license  && storage()->exist($info->license)) {
                $license=storage()->get($info->license);
                $info->license=$license;
            }
            $list[]=$info;
        }
        return $list;
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance=new self;
        }
        return self::$instance;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getCurrentTheme()
    {
        return $this->template[self::$current]??false;
    }

    public function delete(string $name)
    {
        if (!isset($this->template[$name])) {
            return false;
        }
        return storage()->rmdirs($this->template[$name]->path);
    }

    public function upload(string $path, string $fileName=null)
    {
        $fileName=$fileName??basename($path);
        $name=substr($fileName, 0, strrpos($fileName, '.'));
        return ZipHelper::unzip($path, $this->base.'/'.$name, true);
    }

    public static function getTemplates()
    {
        $modules=$modules??app()->getLiveModules();
        $templates=[];
        foreach ($modules as $module) {
            if (!app()->checkModuleExist($module)) {
                continue;
            }
            $templates[$module]= SudaTemplateManager::findModuleTemplates($module);
        }
        return $templates;
    }

    public static function exportTemplate(string $output)
    {
        storage()->delete($output);
        $theme=self::instance()->getCurrentTheme();
        $base=$output;
        storage()->copydir($theme->path, $base);
        $base=isset($theme->config['root'])?$base.'/'.$theme->config['root']:$base;
        foreach (($theme->config['modules']??[]) as $module=>$moduleTemplate) {
            $outputPath=$base.'/'.$moduleTemplate;
            storage()->path($outputPath);
            // 复制模板
            $templates=SudaTemplateManager::findModuleTemplates($module);
            if (is_array($templates)) {
                foreach ($templates as $name) {
                    if (!storage()->exist($copyTo=$outputPath.'/'.$name.'.tpl.html')) {
                        $templateName=$module.':'.$name;
                        $inputFile=SudaTemplateManager::getInputFile($templateName);
                        if ($inputFile) {
                            storage()->path(dirname($copyTo));
                            storage()->copy($inputFile, $copyTo);
                        }
                    }
                }
            }
            // 复制静态文件与其他文件
            $sources=SudaTemplateManager::getTemplateSource($module);
            if (is_array($sources)) {
                foreach ($sources as $source) {
                    if ($path=Storage::abspath($source.'/static')) {
                        storage()->path($outputPath.'/static');
                        storage()->copydir($path, $outputPath.'/static');
                    }
                    storage()->copydir($source, $outputPath, '/(?<!\.tpl\.html)$/');
                }
            }
        }
    }
}
