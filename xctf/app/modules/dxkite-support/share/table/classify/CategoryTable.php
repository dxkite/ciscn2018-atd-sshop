<?php
namespace dxkite\support\table\classify;

use suda\archive\Table;
use suda\core\Query;

abstract class CategoryTable extends Table
{
    public function onBuildCreator($table)
    {
        return $table->fields(
            $table->field('id', 'bigint', 20)->primary()->unsigned()->auto(),
            $table->field('name', 'varchar', 255)->unique()->comment("分类名"),
            $table->field('slug', 'varchar', 255)->unique()->comment("分类缩写"),
            $table->field('user', 'bigint', 20)->unsigned()->key()->comment("创建用户"),
            $table->field('count', 'bigint', 20)->key()->comment("文章统计"),
            $table->field('parent', 'bigint', 20)->key()->comment("父分类")
        );
    }

    public function ids2name(array $ids)
    {
        $get=$this->select(['id','name'], ['id'=>$ids])->fetchAll();
        if ($get) {
            $result=[];
            foreach ($get as $item) {
                $result[$item['id']]=$item['name'];
            }
            return $result;
        }
        return false;
    }

    public function name2id(string $name)
    {
        if (is_null($page)) {
            return Query::that($this)->where($this->getTableName(), $this->getFields(), ' LOWER(name)=LOWER(:name) ', ['name'=>$name])->fetch()['id']??false;
        }
        return Query::that($this)->where($this->getTableName(), $this->getFields(), ' LOWER(name)=LOWER(:name) ', ['name'=>$name], [$page,$row])->fetch()['id']??false;
    }

    public function slug2id(string $slug)
    {
        if (is_null($page)) {
            return Query::that($this)->where($this->getTableName(), $this->getFields(), ' LOWER(slug)=LOWER(:slug) ', ['slug'=>$name])->fetch()['id']??false;
        }
        return Query::that($this)->where($this->getTableName(), $this->getFields(), ' LOWER(slug)=LOWER(:slug) ', ['slug'=>$name], [$page,$row])->fetch()['id']??false;
    }

    public function plus(int $cateid)
    {
        return Query::that($this)->update($this->getTableName(), 'count = count +1', ['id'=>$cateid]);
    }
    
    public function minus(int $cateid)
    {
        return Query::that($this)->update($this->getTableName(), 'count = count -1', ['id'=>$cateid]);
    }
    
    public function add(int $uid, string $name, string $slug, int $parent=0)
    {
        return $this->insert([
            'name'=>$name,
            'slug'=>$slug,
            'parent'=>$parent,
            'user'=>$uid,
        ]);
    }
}
