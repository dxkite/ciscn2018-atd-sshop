<?php
namespace dxkite\support\database;

use dxkite\support\proxy\ProxyObject;
use suda\archive\SQLQuery;
use suda\archive\TableInstance;
use suda\tool\Json;
use suda\tool\ZipHelper;
use dxkite\support\file\File;


class DbHelper
{
    const PATH=DATA_DIR.'/backup';
    protected static $tableClasses;
    /**
     * @acl list_tables
     * 列出数据表信息
     *
     * @return void
     */
    public static function status()
    {
        $tables= self::getTableClasses();
        $tableInfos=[];
        foreach ($tables  as $name=>$class) {
            $tableName=self::table($name)->getTableName();
            $status= (new SQLQuery('SHOW TABLE STATUS LIKE \'#{'.$tableName.'}\''))->fetch();
            $tableInfo=new TableInfo;
            $tableInfo->realName=$status['Name'];
            $tableInfo->rows=$status['Rows'];
            $tableInfo->create=$status['Create_time'];
            $tableInfo->size=$status['Data_length'];
            $tableInfo->name=$name;
            $tableInfos[]=$tableInfo;
        }
        return $tableInfos;
    }
    
    public static function getTableClasses()
    {
        if (self::$tableClasses != null) {
            return self::$tableClasses;
        }
        $modules=app()->getLiveModules();
        $tables=[];
        foreach ($modules as $module) {
            if (storage()->exist($jsonfile=app()->getModulePath($module).'/resource/config/table.json')) {
                $tmp=Json::loadFile($jsonfile);
                $perfix=$tmp['prefix']??'';
                if (is_array($tmp['tables']??false)) {
                    foreach ($tmp['tables'] as $name=>$tableClass) {
                        $tables[$perfix.$name]=$tableClass;
                    }
                }
            }
        }
        return self::$tableClasses=$tables;
    }

    public static function table(string $name)
    {
        $class=self:: getTableClasses();
        $tableClassName = class_name($class[$name]);
        return new $tableClassName;
    }

    /**
     * 创建数据库备份
     *
     * @acl create_backup
     * @return void
     */
    public static function create()
    {
        $hash=date('Y-m-d_H.i.s_').substr(md5(time()), 0, 8);
        storage()->path(self::PATH.'/'.$hash);
        $config=new Config;
        $config->create=time();
        $config->tables=[];
        $config->modify=time();
        storage()->put(self::PATH.'/'.$hash.'/config.json', json_encode($config));
        return $hash;
    }

    /**
     * 删除备份
     *
     * @acl  delete_backup
     * @param string $hash
     * @return void
     */
    public static function deleteBackup(string $hash)
    {
        if (storage()->exist($configFile=self::PATH.'/'.$hash.'/config.json')) {
            return storage()->delete(self::PATH.'/'.$hash);
        } else {
            return false;
        }
    }


    /**
     * 备份表到一备份数据
     *
     * @acl export_table
     * @param string $hash
     * @param string $table
     * @return void
     */
    public static function backupTo(string $hash, string  $table)
    {
        if (storage()->exist($configFile=self::PATH.'/'.$hash.'/config.json')) {
            $content=storage()->get($configFile);
            $config=json_decode($content);
            $config->modify=time();
            if (!in_array($table, $config->tables)) {
                $config->tables[]=$table;
            }
            if (self::table($table)->export(self::PATH.'/'.$hash.'/'.$table.'.base64')) {
                storage()->put($configFile, json_encode($config));
            } else {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 从某一备份中导入数据
     *
     * @acl import_table
     * @param string $hash
     * @param string $table
     * @return void
     */
    public static function importFrom(string $hash, string $table)
    {
        if (storage()->exist($configFile=self::PATH.'/'.$hash.'/config.json')) {
            $result= self::table($table)->import(self::PATH.'/'.$hash.'/'.$table.'.base64');
        } else {
            return false;
        }
    }

    /**
     * 从某一备份中强制导入数据
     *
     * @acl import_table
     * @param string $hash
     * @param string $table
     * @return void
     */
    public static function forceImportFrom(string $hash, string $table)
    {
        if (storage()->exist($configFile=self::PATH.'/'.$hash.'/config.json')) {
            self::table($table)->query('SET FOREIGN_KEY_CHECKS = 0')->exec();
            self::table($table)->delete('1');
            $result= self::table($table)->import(self::PATH.'/'.$hash.'/'.$table.'.base64');
            self::table($table)->query('SET FOREIGN_KEY_CHECKS = 1')->exec();
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 优化数据表
     * @acl optimize_tables
     * @param array $tables
     * @return void
     */
    public static function optimize(array $tables)
    {
        foreach ($tables  as $name) {
            $tableArr[]='`#{'.$name.'}`';
        }
        return (new SQLQuery('OPTIMIZE TABLE '. implode(',', $tableArr)))->exec();
    }

    /**
     * 修复数据表
     * @acl repair_tables
     * @param array $tables
     * @return void
     */
    public static function repair(array $tables)
    {
        foreach ($tables  as $name) {
            $tableArr[]='`#{'.$name.'}`';
        }
        return (new SQLQuery('REPAIR TABLE '. implode(',', $tableArr)))->exec();
    }

    /**
     * 列出备份
     * @acl list_backups
     * @return void
     */
    public static function list()
    {
        $readDirs=storage()->readDirs(self::PATH);
        $configs=[];
        foreach ($readDirs as $dir) {
            $config_path=self::PATH.'/'.$dir.'/config.json';
            if (storage()->exist($config_path)) {
                $conf=storage()->get($config_path);
                $configs[$dir]= json_decode($conf);
            } else {
                storage()->delete(self::PATH.'/'.$dir);
            }
        }
        return $configs;
    }

    public static function backupInfo(string $hash)
    {
        $config_path=self::PATH.'/'.$hash.'/config.json';
        if (storage()->exist($config_path)) {
            $conf=storage()->get($config_path);
            return json_decode($conf);
        } else {
            storage()->delete(self::PATH.'/'.$hash);
        }
        return false;
    }

    public static function backupPath(string $hash)
    {
        $config_path=self::PATH.'/'.$hash.'/config.json';
        if (storage()->exist($config_path)) {
            return self::PATH.'/'.$hash;
        } else {
            storage()->delete(self::PATH.'/'.$hash);
        }
        return false;
    }

    public static function upload(File $backupFile)
    {
        $name=$backupFile->getName();
        return ZipHelper::unzip($backupFile->getPath(),self::PATH.'/'.$name);
    }
}
