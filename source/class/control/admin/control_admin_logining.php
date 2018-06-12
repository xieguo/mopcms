<?php
/**
 * 后台登陆控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_logining extends control_admin
{
    public function __construct(){}
    /**
     * 登陆页面
     */
    public function logining_login()
    {
        global $_M;
        $_data = array();
        $_data['menuname'] = '登陆';
        $_data['template'] = 'login';
        $_data['referer'] = urldecode(_gp('referer'));
        if(!empty($_M['admin']['id'])){
            $referer = !empty($_data['referer'])?$_data['referer']:'?';
            header("location:".$referer);
            exit();
        }
        return getResult(100,$_data);
    }

    /**
     * 登陆处理操作
     */
    public function logining_login_submit()
    {
        $username = filterString(_gp('username'));
        $pwd = filterString(_gp('pwd'));
        $seccode = strtolower(filterString(_gp('seccode')));
        $result = loadm('admin')->login($username,$pwd,$seccode);
        return $this->output($result,false);
    }

    /**
     * 退出登陆
     */
    public function logining_logout()
    {
        global $_M;
        require_once loadlib('archives');
        clearAttachCache();
        set_cookie('adminauth');
        $referer = filterString(_gp('referer'));
        header("location:?mod=logining&ac=login&referer=" . urlencode($referer));
        exit();
    }
}