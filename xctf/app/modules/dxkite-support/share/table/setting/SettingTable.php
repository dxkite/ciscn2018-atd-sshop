<?php
namespace dxkite\support\table\setting;

use suda\archive\Table;

class SettingTable extends Table
{
    public function __construct()
    {
        parent::__construct('setting');
    }

    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->auto(),
            $table->field('name', 'varchar', 255)->unique()->comment("设置名"),
            $table->field('value', 'text')->comment("设置的值")
        );
    }

    public function set(string $name, $value)
    {
        $get=$this->select('id', ['name'=>$name]);
        if ($get && ($val=$get->fetch())) {
            $id=$val['id'];
            $count= $this->updateByPrimaryKey($id, ['value'=>$value]);
        } else {
            $id=$this->insert(['name'=>$name, 'value'=>$value]);
        }
        return $id;
    }

    public function get(string $name)
    {
        return $this->select(['id','value'], ['name'=>$name]);
    }
    
    public function remove(string $name)
    {
        $get=$this->select('id', ['name'=>$name]);
        if ($get && ($val=$get->fetch())) {
            $id=$val['id'];
            return $this->deleteByPrimaryKey($id);
        }
        return false;
    }
    
    public function list(int $page=null, int $rows=10, bool $offset=false)
    {
        if (is_null($page)) {
            $list=parent::list();
        } else {
            $list=parent::list($page, $rows, $offset);
        }
        return $list;
    }
    
    public function _inputValueField($value)
    {
        return serialize($value);
    }

    public function _outputValueField($value)
    {
        return unserialize($value);
    }
}
