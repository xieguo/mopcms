<?php
/**
 * 后台单页文档管理控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_singlepage extends control_admin
{
    /**
     * 列表页面
     */
    public function singlepage_list()
    {
        $res = t('singlepage')->info_list();
        return $this->output($res);
    }

    /**
     * 修改添加页面
     */
    public function singlepage_save()
    {
        $id = (int)_gp('id');
        $row = $id?t('singlepage')->fetch($id):array();
        return $this->output(array('row'=>$row));
    }

    /**
     * 修改添加文档
     */
    public function singlepage_save_submit()
    {
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['title'] = filterString(_gp('title'));
        $post['keywords'] = filterString(_gp('keywords'));
        $post['description'] = filterString(_gp('description'));
        $post['template'] = filterString(_gp('template'));
        $post['link'] = filterString(_gp('link'));
        $post['content'] = maddslashes(_gp('content'));
        $post['adminid'] = $this->admin['id'];
        $result = loadm('admin_singlepage')->save($post);
        return $this->output($result,false);
    }

    /**
     * 更新操作
     * @param unknown_type $id
     */
    public function singlepage_refresh()
    {
        $id = (int) _gp('id');
        $result = loadm('admin_singlepage')->refresh($id);
        return $this->output($result,false);
    }

    /**
     * 删除操作
     */
    public function singlepage_del()
    {
        $id = (int)_gp('id');
        $result = loadm('admin_singlepage')->del($id);
        return $this->output($result,false);
    }
}