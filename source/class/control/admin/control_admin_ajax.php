<?php
/**
 * 后台AJAX控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_ajax extends control_admin
{
    /**
     * 站内图片
     */
    public function ajax_ueditor_imgs()
    {
        global $_M;
        $result = loadm('misc')->ueditor_imgs($_M['admin']['id'],$_M['page']);
        return getResult(100,$result);
    }

    /**
     * 通过swfupload上传图片
     */
    public function ajax_swfupload_pointimg()
    {
        global $_M;
        $result = loadm('misc')->swfupload_image('thumb_tmp',$_M['sys']['img_width'],$_M['sys']['img_height']);
        getjson($result);
    }

    /**
     * 调整图片大小
     */
    public function ajax_swfupload_imgresize()
    {
        $pointid = (int)_gp('pointid');
        $img = filterString(_gp('img'));
        $result = loadm('admin_location')->location_point_info($pointid);

        if($result['code']!=100){
            return $result;
        }
        if($result['data']['imgwidth']){
            $result = loadm('misc')->imageResize($img,$result['data']['imgwidth'],$result['data']['imgheight']);
        }
        return getResult(100);
    }

    /**
     * 汉字转拼音
     */
    public function ajax_column_pinyin()
    {
        $name = filterString(_gp('name'));
        $result = loadm('misc')->to_pinyin($name);
        return getResult(100,$result);
    }

    public function ajax_column_ajaxcolumn()
    {
        $id = (int)_gp('id');
        $columnid = (int)_gp('columnid');
        $modelid = (int)_gp('modelid');
        return controlCache('admin_column','column_ajaxcolumn',$id,$columnid,$modelid,'column');
    }

    /**
     * 某定位点信息列表
     */
    public function ajax_location_infolist()
    {
        $pointid = (int)_gp('pointid');
        return controlCache('admin_location','location_infolist',$pointid,'location_infolist');
    }

    /**
     * 删除定位点的某条信息
     */
    public function ajax_location_infodel()
    {
        $id = (int)_gp('id');
        return loadm('archives')->del_pointinfo($id);
    }

    /**
     * 编辑定位点的某条信息
     */
    public function ajax_location_infoedit()
    {
        $id = (int) _gp('id');
        $pointid = (int)_gp('pointid');
        $_data = array();
        $_data['info'] = loadm('admin_location')->location_infolist_id($id);
        $point = loadm('admin_location')->location_point_info($pointid);
        $_data['point'] = $point['data'];
        return $this->output($_data,false);
    }

    /**
     * 某定位点信息
     */
    public function ajax_location_point()
    {
        $id = (int)_gp('id');
        $aid = (int)_gp('aid');
        $result = loadm('admin_location')->location_point_info($id);
        $result['data']['arc'] = loadm('archives')->fetch_by_id($aid);
        return $result;
    }

    /**
     * 左侧菜单
     */
    public function ajax_leftmenu()
    {
        global $_M;
        $menuid = _gp('menuid')?(int)_gp('menuid'):1;
        $result = loadm('admin')->leftmenu($menuid);
        extract($result,EXTR_SKIP);
        $common_operate = controlCache('admin_main','common_operate',$this->admin['id'],3600);
        $lastest_operate = controlCache('admin_main','lastest_operate',$this->admin['id'],3600);
        include template('sidebar');
        $str = output();
        return getResult(100,$str);
    }
}