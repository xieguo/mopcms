<?php
/**
 * 后台系统设置控制类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_setting extends control_admin
{
    /**
     * 管理员分组页面
     */
    public function setting_admingroups()
    {
        $_data = array();
        $_data['result'] = t('admin_group')->info_list();
        return $this->output($_data);
    }

    /**
     * 修改管理员分组修改页面
     */
    public function setting_admingroups_save()
    {
        $id = (int) _gp('id');
        $result = loadm('admin_setting')->admingroups_save($id);
        return $this->output($result);
    }
    /**
     * 修改管理员分组修改操作
     */
    public function setting_admingroups_save_submit()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['groupname'] = filterString(_gp('groupname'));
        $post['purviews'] = filterString(_gp('purviews'));
        $post['mpurviews'] = filterString(_gp('mpurviews'));
        $post['cpurviews'] = filterString(_gp('cpurviews'));
        $result = loadm('admin_setting')->admingroups_save_submit($post);
        $result['referer'] = $_M['cururl'] . '&do=&id=';
        return $this->output($result,false);
    }

    /**
     * 管理员分组删除操作
     */
    public function setting_admingroups_del()
    {
        global $_M;
        $id = (int) _gp('id');
        if(empty($id)) {
            return getResult(10001);
        }
        t('admin_group')->delete($id);
        return getResult(100, '', 10505, $_M['cururl'] . '&do=&id=');
    }

    /**
     * 管理员页面
     */
    public function setting_admins()
    {
        $post = array();
        $post['words'] = filterString(_gp('words'));
        $post['groupid'] = (int)_gp('groupid');
        $this->cururl($post);
        $result = controlCache('admin_setting','admins',$post,'admin');
        $result['data'] += $post;
        return $this->output($result);
    }

    /**
     * 修改管理员添加页面
     */
    public function setting_admins_save()
    {
        $id = (int)_gp('id');
        $result = loadm('admin_setting')->admins_save($id);
        return $this->output($result);
    }

    /**
     * 修改管理员修改操作
     */
    public function setting_admins_save_submit()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['columnids'] = filterString(_gp('columnids'));
        $post['purviews'] = filterString(_gp('purviews'));
        $post['nickname'] = filterString(_gp('nickname'));
        $post['realname'] = filterString(_gp('realname'));
        $post['groupid'] = (int) _gp('groupid');
        $post['pwd'] = preg_replace('/[^\w]/i', '', _gp('pwd'));
        $post['username'] = preg_replace('/[^\w]/i', '', _gp('username'));
        $result = loadm('admin_setting')->admins_save_submit($post);
        return $this->output($result,false);
    }

    /**
     * 管理员分组删除操作
     */
    public function setting_admins_del()
    {
        global $_M;
        $id = (int) _gp('id');
        if(empty($id)) {
            return getResult(10001);
        }
        t('admin')->delete($id);
        return getResult(100, '', 10505, $_M['cururl'] . '&do=&id=');
    }

    /**
     *系统参数设置页面
     */
    public function setting_parameter()
    {
        $result = controlCache('admin_setting','parameter','sys_setting');
        return $this->output($result);
    }
    /**
     * 系统参数设置操作
     */
    public function setting_parameter_submit()
    {
        global $_M;
        $post = array();
        $post['value'] = _gp('value');
        $post['name'] = preg_replace('/[^\w]/', '', _gp('name'));
        $post['types'] = filterString(_gp('types'));
        $post['groupid'] = (int) _gp('groupid');
        $post['intro'] = filterString(_gp('intro'));
        foreach($_POST as $k => $v) {
            $k = filterString($k);
            $post['parameter'][$k] = $v;
        }
        $result = loadm('admin_setting')->parameter_submit($post);
        $result['referer'] = $_M['cururl'];
        return $this->output($result,false);
    }

    /**
     * 水印设置页面
     */
    public function setting_watermark()
    {
        global $_M;
        loadcache('watermark');
        $_data = array();
        $row = $_M['cache']['watermark'];
        $row['types'] = $row['types']==2?2:1;
        $_data['row'] = $row;
        return $this->output($_data);
    }
    /**
     *水印设置操作
     */
    public function setting_watermark_submit()
    {
        global $_M;
        loadcache('watermark');
        $row = $_M['cache']['watermark'];
        $data = array();
        $data['enable'] = (int)_gp('enable')?1:0;
        $data['types'] = _gp('types')==2?2:1;
        $data['width'] = (int)_gp('width');
        $data['height'] = (int)_gp('height');
        $data['markpos'] = min(max(1,(int)_gp('markpos')),9);
        $data['marktext'] = filterString(_gp('marktext'));
        $data['fontsize'] = (int)_gp('fontsize');
        $data['fontcolor'] = filterString(_gp('fontcolor'));
        $data['marktrans'] = (int)_gp('marktrans')?(int)_gp('marktrans'):85;
        $data['diaphaneity'] = (int)_gp('diaphaneity')?(int)_gp('diaphaneity'):60;
        $data['markimg'] = $row['markimg'];
        if($data['types']==1){
            $img = new mop_image;
            $result = $img->uploadAttachment('markimg',$data['width'],$data['height']);
            if(!empty($result['data'])){
                $data['markimg'] = $result['data'];
                $row['markimg']!='/static/images/watermark.png' && loadm('admin')->del_image($row['markimg']);
            }
        }
        t('sys_cache')->save('watermark', $data);
        $result = getResult(100,'',10506);
        return $this->output($result,false);
    }

    /**
     * 后台日志列表
     * @param unknown_type $post
     */
    public function setting_logs()
    {
        $post = array();
        $post['words'] = filterString(_gp('words'));
        $post['adminid'] = (int)_gp('adminid');
        $this->cururl($post);
        $result = loadm('admin_setting')->logs($post);
        $result['data'] += $post;
        return $this->output($result);
    }

    /**
     * 删除后台日志
     * @param unknown_type $timestamp
     */
    public function setting_logs_del()
    {
        $timestamp = filterString(_gp('timestamp'));
        $result = loadm('admin_setting')->logs_del($timestamp);
        return $this->output($result,false);
    }

    /**
     * 系统升级
     */
    public function setting_upgrade()
    {
        global $_M;
        $token = $this->appToken();
        header('location:'.$_M['mopcmsurl'].'main.php?mod=upgrade&adminurl='.$_M['basescript'].'&v='.$_M['sys']['version'].'&token='.$token);
        exit;
    }

    /**
     * 系统升级检测
     */
    public function setting_upgrade_check()
    {
        global $_M;
        $v = file_get_contents($_M['mopcmsurl'].'data/version.txt');
        $v = trim($v);
        $data = array('upgrade'=>0);
        if($v && $v>$_M['sys']['version']){
            $data = array('upgrade'=>1);
        }
        getjson($data);
    }

    /**
     * 系统升级返回页面
     */
    public function setting_upgrade_result()
    {
        $curv = (float)_gp('v');
        $finalv = (float)_gp('finalv');
        $result = loadm('admin_setting')->upgradeResult($curv,$finalv);
        $result['data']['finalv'] = $finalv;
        return $this->output($result);
    }
    public function setting_upgrade_result_submit()
    {
        $curv = (float)_gp('v');
        $finalv = (float)_gp('finalv');
        $result = loadm('admin_setting')->upgradeResult_submit($curv,$finalv);
        return $this->output($result,false);
    }
}