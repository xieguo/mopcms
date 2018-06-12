<?php
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin extends base_model
{
    /**
     * 登陆处理操作
     */
    public function login($username,$pwd,$seccode)
    {
        global $_M;
        require_once loadlib('misc');
        if (empty ($username) || empty ($pwd) || empty ($seccode)) {
            return getResult(10002);
        }
        if ($seccode != strtolower(get_seccode())) {
            return getResult(10003);
        }
        $pwd = $this->create_pwd($pwd);
        $row = t('admin')->fetchFields(array ('username' => $username));
        if (empty ($row)) {
            return getResult(10004);
        }
        if ($pwd != $row['pwd']) {
            return getResult(10101);
        }
        $row['purviews'] = t('admin_group')->fetchFields($row['groupid'], 'purviews');
        t('admin')->update($row['id'], array ('loginip' => $_M['ip'],'logintime' => TIME));
        set_cookie('adminauth', xxtea($row['id'], 'en'));
        return getResult(100,'',10032);
    }

    /**
     * 后台左侧栏目等相关数据
     * @param unknown_type $menuid
     */
    public function leftmenu($menuid)
    {
        $data = array();
        $data['admin_menu'] = t('admin_menu')->get_cache('menuList','',600);
        $row = t('admin_menu')->fetch($menuid);
        $data['menuname'] = $row['name'];
        $data['menuparentid'] = $row['parentid'];
        $data['topid'] = t('admin_menu')->getTopid($menuid);
        $data['location'] = t('admin_menu')->getLocal($menuid);
        $data['sidebar_models'] = t('column')->groupByModelid();
        return $data;
    }
}