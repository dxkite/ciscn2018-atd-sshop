<?php
namespace dxkite\support\table\classify;

use suda\archive\Table;
use suda\core\Query;

abstract class TagsTable extends Table
{
    public function onBuildCreator($table){
        return $table->fields(
            $table->field('id','bigint',20)->primary()->unsigned()->auto(),
            $table->field('name','varchar',255)->unique()->comment("标签名"),
            $table->field('user','bigint',20)->unsigned()->key()->comment("创建用户"),
            $table->field('time','int')->key()->comment("创建时间")
        );
    }

    public function add(int $user, string $name)
    {
        if ($tag=self::exist($name)) {
            return $tag['id'];
        } else {
            return $this->insert(['name'=>$name,'user'=>$user,'time'=>time()]);
        }
    }

    public function setTags(int $uid,int $aid,array $tags){
        $get=(new ArticleDAO)->getUserArticle($uid,$aid);
        if($get){
            return (new TagDAO)->bind($aid,$tags);
        }
        return false;
    }

    /**
     * 获取文章标签ID
     *
     * @param int $aid
     * @return void
     */
    public function getTags(int $aid){
         return (new TagDAO)->get($aid);
    }

    public function exist(string $name)
    {
        return Query::where($this->getTableName(), $this->getFields(), ' LOWER(name)=LOWER(:name) ', ['name'=>$name])->fetch();
    }

    /**
     * 获取同标签文章ID列表
     *
     * @param string $name
     * @param int $page
     * @param int $row
     * @return void
     */
    public function getId(string $name, int $page=null, int $row=10)
    {
        if (is_null($page)) {
            return Query::where($this->getTableName(), $this->getFields(), ' LOWER(name)=LOWER(:name) ', ['name'=>$name])->fetch()['id']??false;
        }
        return Query::where($this->getTableName(), $this->getFields(), ' LOWER(name)=LOWER(:name) ', ['name'=>$name], [$page,$row])->fetch()['id']??false;
    }

    public function ids2name(array $ids)
    {
        return $this->select(['id','name'], ['id'=>$ids])->fetchAll();
    }

    public function name2id(string $name)
    {
        return Query::where($this->getTableName(), ['id'], ' LOWER(name)=LOWER(:name) ', ['name'=>$name])->fetch()['id']??false;
    }
}
