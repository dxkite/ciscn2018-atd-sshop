<?php

namespace dxkite\support\visitor\exception;

class RPCException extends \Exception
{
    private $id;
    protected $data;

    public function setId(int $id)
    {
        $this->id=$id;
        return $this;
    }

    public function setData($data)
    {
        $this->data=$data;
        return $this;
    }

    public function toArray()
    {
        return [
            'jsonrpc'=>'2.0',
            'error'=>[
                'code'=>$this->getCode(),
                'message'=>$this->getMessage(),
                'data'=>$this->data,
            ],
            'id'=>$this->id
        ];
    }
}
