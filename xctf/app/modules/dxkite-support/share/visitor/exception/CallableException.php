<?php

namespace dxkite\support\visitor\exception;

class CallableException extends \Exception
{
    protected $name;
    protected $id;
    protected $data;

    public function setName(string $name)
    {
        $this->name=$name;
        return $this;
    }
    public function setData($data)
    {
        $this->data=$data;
        return $this;
    }
        
    public function getData()
    {
        return $this->data;
    }
    public function setId(int $id)
    {
        $this->id=$id;
        return $this;
    }
    public function getName()
    {
        return $this->name??__CLASS__;
    }

    public function toArray()
    {
        return [
            'error'=>[
                'code'=>$this->getCode(),
                'name'=>$this->getName(),
                'message'=>$this->getMessage(),
                'data'=>$this->data,
            ],
            'id'=>$this->id
        ];
    }
}
