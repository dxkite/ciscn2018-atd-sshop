<?php
namespace dxkite\support\visitor;

use suda\core\Storage;
use suda\core\Application;
use dxkite\support\user\Session;
use dxkite\support\image\VeCode;
use dxkite\support\visitor\Context;

class VerifyCode
{
    private static $verifyschars='mnbvcxzasdfghjklpoiuytrewq1234567890QWERTYUIOPLKJHGFDSAZXCVBNM';
    private $foint;
    private $context;
    private $name;

    public function __construct(string $name)
    {
        $foints=storage()->readDirFiles(app()->getModulePath('support').'/resource/ttf/');
        $this->foint=$foints[mt_rand(0, count($foints)-1)];
        $this->context=Context::getInstance();
        $this->name=$name;
    }

    public function display()
    {
        $vecode= new VeCode($this->foint, 18, 4);
        $randCode=$vecode->generate();
        $vecode->display(VeCode::IMG_PNG);
        $this->context->setSession($this->name, strtoupper($randCode));
    }

    public function hasCode()
    {
        return $this->context->hasSession($this->name);
    }

    public function checkCode(string $code):bool
    {
        $result=strtoupper($verify=$this->context->getSession($this->name))===strtoupper($code);
        self::refresh();
        return $result;
    }

    public function refresh()
    {
        $this->context->setSession($this->name, null);
    }
}
