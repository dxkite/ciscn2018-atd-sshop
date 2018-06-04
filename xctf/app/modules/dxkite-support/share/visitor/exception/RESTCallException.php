<?php

namespace dxkite\support\visitor\exception;

class RESTCallException extends \Exception {
    protected $name;
    protected $data;

    public function setName(string $name){
        $this->name=$name;
        return $this;
    }
    public function setData($data){
        $this->data=$data;
        return $this;
    }
        
    public function getData(){
        return $this->data;
    }
    
    public function getName() {
        return $this->name??__CLASS__;
    }   
}