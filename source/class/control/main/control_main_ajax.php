<?php
/**
 * AJAX相关控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_main_ajax extends control_main
{
    /**
     * 某活动报名数量统计
     */
    public function ajax_entrycount()
    {
        $sid = (int)_gp('sid');
        $rs = t('specials_entry')->count(array('sid'=>$sid));
        return $this->output($rs,false);
    }
    
    /**
     * 登陆信息
     */
    public function ajax_loginStatus()
    {
        global$_M;
        $str = '';
        if(pluginIsAvailable('member')) {
            loadcache('plugin:member');
            require_once loadlib('member:mop');
            $referer = filterString(_gp('referer'));
            if($_M['iswap']){
                echo '<a href="'.rewriteUrl('?mod=login&referer='.urlencode($referer)).'" class="icon icon-84 f-white f28"></a>';
                exit;
            }
            if($_M['mid']){
                $str = $_M['username'].'<a href="'.rewriteUrl('?mod=home').'">个人中心</a> <a href="'.rewriteUrl('?mod=logout&referer='.urlencode($referer)).'">退出</a>';
            }else{
                $str = '<a href="'.rewriteUrl('?mod=login&referer='.urlencode($referer)).'">登录</a> <a href="'.rewriteUrl('?mod=register').'">注册</a>';
            }
        }
        echo $str;exit;
    }
}