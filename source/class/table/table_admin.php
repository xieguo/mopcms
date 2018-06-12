<?php

/**
 * 后台管理员类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_admin extends base_table
{
    /**
     * 管理员登陆信息
     */
    public function loginInfo()
    {
        if($auth = get_cookie('adminauth')) {
            $id = xxtea($auth);
        }
        if(!empty($id)) {
            return $this->fetch($id);
        }
        return array();
    }

    /**
     * 对数据重新处理
     * @param array $data
     */
    protected function parseData(& $data)
    {
        if(!empty($data['logintime'])) {
            $data['_logintime'] = mdate($data['logintime'], 'dt');
        }
        if(!empty($data['columnids'])) {
            $data['_columnids'] = explode(',', $data['columnids']);
        }
        if(!empty($data['groupid'])) {
            $data['_groupid'] = t('admin_group')->get_cache('fetch', $data['groupid']);
        }
    }
}