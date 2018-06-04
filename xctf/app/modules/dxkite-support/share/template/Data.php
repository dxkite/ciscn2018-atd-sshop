<?php
namespace dxkite\support\template;

use dxkite\support\database\DbHelper;
use dxkite\support\database\Config;

class Data
{
    protected $path;
    public function __construct()
    {
        $template=Manager::instance()->getCurrentTheme();
        $this->path=$template->path.'/'.$template->data;
    }

    public function getTemplateTables()
    {
        return array_keys(DbHelper::getTableClasses());
    }

    public function exportTemplateDataTable(string $table)
    {
        if (!storage()->exist($configFile=$this->path.'/config.json')) {
            storage()->path($this->path);
            $config=new Config;
            $config->create=time();
            $config->tables=[];
            $config->modify=time();
            storage()->put($this->path.'/config.json', json_encode($config));
        }
        $content=storage()->get($configFile);
        $config=json_decode($content);
        $config->modify=time();
        if (!in_array($table, $config->tables)) {
            $config->tables[]=$table;
        }
        if (DbHelper::table($table)->export($this->path.'/'.$table.'.base64')) {
            storage()->put($configFile, json_encode($config));
        } else {
            return false;
        }
        return true;
    }

    public function importTemplateDataTable(string $table)
    {
        if (storage()->exist($configFile= $this->path.'/config.json')) {
            DbHelper::table($table)->query('SET FOREIGN_KEY_CHECKS = 0')->exec();
            DbHelper::table($table)->delete('1');
            $result= DbHelper::table($table)->import($this->path.'/'.$table.'.base64');
            DbHelper::table($table)->query('SET FOREIGN_KEY_CHECKS = 1')->exec();
            return $result;
        } else {
            return false;
        }
    }
}
