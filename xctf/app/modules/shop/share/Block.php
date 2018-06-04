<?php

namespace xctf\shop;

use xctf\shop\table\UserTable;
use xctf\shop\table\BlockTable;

class Block
{
    public static function check(string $blockHash)
    {
        $table = new BlockTable;
        return $table -> select('*', ['hash'=>$blockHash])->fetch();
    }
    
    public static function count(int $id)
    {
        $table = new BlockTable;
        return $table -> count(['user'=>$id]);
    }

    public static function dig(int $userId, int $nums)
    {
        $table = new BlockTable;
        $last=$table -> select(['hash'], ' 1 order by time desc limit 1') -> fetch();
      
        $previous_hash = $last['hash'] ?? hash("sha256", 'dxkite');
        
        $target = conf('blockHash', '12');
        for ($i=0;$i<$nums;$i++) {
            $random  = hash("sha256", microtime()) . self::getRandom();
            $str = $random .$previous_hash . $userId;
            $hash =  hash("sha256", $str);
            if (substr(($hash), rand(0, strlen($hash) -  strlen($target)), strlen($target))==$target) {
                $block = [
                    'time' =>  time(),
                    'previous_hash' => $previous_hash,
                    'hash'=>$hash,
                    'nonce'=> $random,
                    'user'=>$userId,
                ];
                if ($id=$table->insert($block)) {
                    $block['id']=$id;
                    return $block;
                }
            }
        }
        return false;
    }

 
    protected static function getRandom()
    {
        $str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        return str_shuffle($str);
    }
}
