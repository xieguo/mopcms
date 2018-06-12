<?php
/**
 * 后台级联数据控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_multilevel extends control_admin
{
    /**
     * 级联数据管理页面
     */
    public function multilevel_list()
    {
        global $_M;
        $parentid = (int)_gp('parentid');
        $result = controlCache('multilevel','multilevel_list',$parentid,'multilevel');
        return $this->output($result);
    }

    /**
     * 级联数据添加
     */
    public function multilevel_add_submit()
    {
        $parentid = (int)_gp('parentid');
        $identifier = preg_replace('/[^\w]/', '', _gp('identifier'));
        $multidata = filterString(_gp('multidata'));
        $result = loadm('multilevel')->multilevel_add($parentid,$multidata,$identifier);
        return $this->output($result,false);
    }
    /**
     * 级联数据修改
     */
    public function multilevel_edit_submit()
    {
        $id = (int)_gp('id');
        $name = filterString(_gp('name'));
        $displayorder = (int)_gp('displayorder');
        $result = loadm('multilevel')->multilevel_edit_submit($id,$name,$displayorder);
        return $this->output($result,false);
    }

    public function multilevel_del()
    {
        $id = (int)_gp('id');
        $result = loadm('multilevel')->multilevel_del($id);
        return $this->output($result,false);
    }
}