<?php
namespace dxkite\support\table\classify;

use suda\archive\Table;

abstract class TagTable extends Table
{
    public function onBuildCreator($table){
        return $table->fields(
            $table->field('id','bigint',20)->primary()->unsigned()->auto(),
            $table->field('bind','bigint',20)->unsigned()->key()->comment('绑定ID'),
            $table->field('tag','bigint',20)->unsigned()->key()->foreign((new TagsTable)->getCreator()->getField('id'))->comment('标签ID')
        );
    }

    /**
     * 根据绑定的ID获取标签ID
     *
     * @param int $id
     * @return void
     */
    public function get(int $id)
    {
        return $this->select(['id','bind','tag'], ['bind'=>$id])->fetchAll();
    }

    /**
     * 根据标签ID获取绑定的ID
     *
     * @param int $tagid
     * @param int $page
     * @param int $row
     * @return void
     */
    public function getBindByTag(int $tagid, int $page=null, int $row=10)
    {
        if (is_null($page)) {
            return $this->listWhere(['tag'=>$tagid]);
        }
        return $this->listWhere(['tag'=>$tagid],[], $page, $row);
    }

    public function bind(int $bind, array $tags)
    {
        $count=0;
        // 剔除已经存在的
        if ($get=$this->select(['bind','tag'], ['bind'=>$bind])->fetchAll()) {
            foreach ($get as $item) {
                $id=array_search($item['tag'], $tags);
                if ($id!==false) {
                    unset($tags[$id]);
                }
            }
        }
        // 添加新的
        foreach ($tags as $tag) {
            $count++;
            $this->insert(['bind'=>$bind,'tag'=>$tag]);
        }
        return $count;
    }
    
    public function unbind(int $bind, array $tags)
    {
        return $this->delete(['bind'=>$bind,'tag'=>$tags]);
    }
}
