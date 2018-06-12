<?php
/**
 * 默认控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_default extends control_admin
{
    /**
     * 后台首页
     */
    public function main()
    {
        global $_M;
        $data = $this->_main();
        $sys = controlCache('admin_main','sys_environment',$_M['config']['db']['dbname'],'sys_setting');
        $data += $sys['data'];
        $stat = controlCache('archives','archives_statistic',true,'archives');
        $data['statistic'] = $stat['data'];
        return $this->output($data);
    }
    private function _main()
    {
        $data = array();
        $data['common_operate'] = controlCache('admin_main','common_operate',$this->admin['id'],3600);
        $data['lastest_operate'] = controlCache('admin_main','lastest_operate',$this->admin['id'],600);
        return $data;
    }

    /**
     * 后台搜索
     */
    public function main_search()
    {
        $words = filterString(_gp('words'));
        $result = loadm('admin_main')->search($words);
        $result['data'] += $this->_main();;
        return $this->output($result);
    }

    /**
     * 回收站文档列表
     */
    public function recyclebin_list()
    {
        $post = array();
        $post['words'] = filterString(_gp('words'));
        $this->cururl($post);
        $result = loadm('admin_archives')->recyclebin_list($post);
        return $this->output($result);
    }

    /**
     * 还原操作
     */
    public function recyclebin_restore()
    {
        $ids = parent::valid_ids();
        $result = loadm('archives')->update_status($ids,1);
        return $this->output($result,false);
    }

    /**
     * 删除操作
     */
    public function recyclebin_del()
    {
        $ids = parent::valid_ids();
        $result = loadm('archives')->archives_del($ids);
        return $this->output($result,false);
    }

    /**
     * 清空回收站
     */
    public function recyclebin_delall()
    {
        $row = t('archives')->fetchList(array('status'=>-1),'id');
        $ids = array();
        foreach ($row as $v){
            $ids[] = $v['id'];
        }
        $result = loadm('archives')->archives_del($ids);
        $this->output($result,false);
        header('location:?mod=recyclebin&ac=list&menuid=39');
        exit;
    }
}