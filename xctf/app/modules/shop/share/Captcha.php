<?php
namespace xctf\shop;

use suda\archive\Table;

class Captcha
{
    public static function getAns(String $uuid)
    {
        $answer=[];
        $file=storage()->get(conf('captcha.ans').'/ans'.$uuid.'.txt');
        $lines = preg_split('/(\r?\n)+/', $file);
        foreach ($lines as $line) {
            if ($line) {
                $p = preg_split('/=/', $line, 2);
                $answer[trim($p[0])]=trim($p[1]);
            }
        }
        return $answer;
    }

    public static function generate()
    {
        $path=config()->get('captcha.ques');
        $files = storage()->readDirFiles($path);
        $key=array_rand($files, 1);
        $ques =  $files[$key];
        $uuid = preg_replace('/^(?:.+)ques(.+)\.jpg$/', '$1', $ques);
        $ans = self::getAns($uuid);
        session()->set('uuid', $uuid);
        session()->set('question',$ans['vtt_ques']);
        session()->set('questionFile', $ques);
        return $uuid;
    }

    public static function check()
    {
        $x = request()->get('captcha_x', request()->post('captcha_x'));
        $y = request()->get('captcha_y', request()->post('captcha_y'));
        if ($x && $y) {
            $uuid = session()->get('uuid');
            $answer = self::getAns($uuid); 
            debug()->info('captcha_info '.$x.' - '.$y.' - '.$uuid,$answer);
            if ($answer['ans_pos_x_1'] <= $x &&  $x <= ($answer['ans_width_x_1'] + $answer['ans_pos_x_1'])) {
                if ($answer['ans_pos_y_1']  <=$y && $y  <= ($answer['ans_height_y_1']  + $answer['ans_pos_y_1'])) {
                    debug()->info('captcha_info success');
                    return true;
                }
            }
            return false;
        }
        return false;
    }
}
