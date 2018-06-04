<?php
namespace dxkite\support;
class Bootstrap
{

    public static function page($template){
        $now=$template->get('page.now',1);
        $max=$template->get('page.max', false);
        $router=$template->get('page.router');
        if ($max!==false) {
            $max=$max<=0?1:$max;
            return self::pageNum($now, $max, $router);
        } else {
            return self::pageUnknowMax($now, $router, $template->get('page.next', true));
        }
    }

    protected static function pageNum(int $now, int $max, string $router)
    {
        echo '<nav aria-label="Page navigation"> <ul class="pagination">';
        if ($now<=1) {
            echo '<li class="page-item disabled"><a class="page-link" href="#"> '.__('上一页') .'</a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="'.u($router,array_merge($_GET,['page'=>$now-1])).'">'.__('上一页') .'</a></li>';
        }
        for ($i=1;$i<=$max;$i++) {
            echo '<li class="page-item'.($now==$i?' active':'').'"><a class="page-link" href="'.($now==$i?'#':u($router,array_merge($_GET,['page'=>$i]))) .'">'.$i.'</a></li>';
        }
        if ($now>=$max) {
            echo '<li class="page-item disabled"><a class="page-link" href="#">'. __('下一页') .'</a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="'.u($router, array_merge($_GET,['page'=>$now+1])).'">'. __('下一页') .'</a></li>';
        }
        echo '</ul></nav>';
    }
    protected static function pageUnknowMax(int $now, string $router, bool $next=true)
    {
        echo '<nav aria-label="Page navigation"> <ul class="pagination justify-content-between">';
        if ($now<=1) {
            echo '<li class="page-item disabled"><a class="page-link" href="#"> '.__('上一页') .'</a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="'.u($router,array_merge($_GET,['page'=>$now-1])).'">'.__('上一页') .'</a></li>';
        }
        if ($next) {
            echo '<li class="page-item"><a class="page-link" href="'.u($router, array_merge($_GET,['page'=>$now+1])).'">'. __('下一页') .'</a></li>';
        } else {
            echo '<li class="page-item disabled"><a class="page-link" href="#">'. __('下一页') .'</a></li>';
        }
        echo '</ul></nav>';
    }
}
