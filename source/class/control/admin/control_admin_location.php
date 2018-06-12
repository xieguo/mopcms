<?php
/**
 * 后台定位点控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_location extends control_admin
{
    /**
     * 定位点展示页面
     */
    public function location_point()
    {
        $pageid = (int) _gp('pageid');
        $result = loadm('admin_location')->location_point($pageid);
        return $this->output($result);
    }

    /**
     * 定位点处理操作
     */
    public function location_point_submit()
    {
        $pointid = (int)_gp('pointid');
        $data = array();
        $data['title'] = filterString(_gp('title'));
        $data['pageid'] = (int)_gp('pageid');
        $data['coords'] = preg_replace('/[^\d,]/', '', _gp('coords'));
        $data['imgwidth'] = (int)_gp('imgwidth');
        $data['imgheight'] = (int)_gp('imgheight');
        $data['titlelen'] = (int)_gp('titlelen');
        $data['summarylen'] = (int)_gp('summarylen');
        $data['infonums'] = (int)_gp('infonums');
        $result = loadm('admin_location')->location_point_submit($pointid,$data);
        return $this->output($result,false);
    }

    /**
     * 删除定位点
     */
    public function location_point_del()
    {
        $pointid = (int) _gp('pointid');
        $result = loadm('admin_location')->location_point_del($pointid);
        return $this->output($result,false);
    }

    /**
     * 修改定位点坐标
     */
    public function location_point_editcoord()
    {
        $pointid = (int) _gp('pointid');
        $coords = preg_replace('/[^\d,]/i', '', _gp('coords'));
        $result = loadm('admin_location')->location_point_editcoord($pointid,$coords);
        return $this->output($result,false);
    }

    /**
     * 获取定位点信息
     */
    public function location_point_pointinfo()
    {
        $pointid = preg_replace('/[^\d,]/i','',_gp('pointid'));
        if(empty($pointid)){
            $result = array();
        }
        elseif(strpos($pointid, ',')!==false){
            $result = t('location_points')->fetchFields(array('coords'=>$pointid));
        }else{
            $result = t('location_points')->fetch($pointid);
        }
        return $this->output($result,false);
    }

    /**
     * 某定位点信息数量
     */
    public function location_point_infocount()
    {
        $pointid = (int) _gp('pointid');
        $count = t('location_infolist')->count(array('pointid' => $pointid));
        return $this->output($count,false);
    }

    /**
     * 定位页面展示
     */
    public function location_page()
    {
        $result = loadm('admin_location')->location_page();
        return $this->output($result);
    }
    /**
     * 定位页面添加修改
     */
    public function location_page_submit()
    {
        $id = (int)_gp('id');
        $name = filterString(_gp('name'));
        if(empty($name)){
            return getResult(10001);
        }
        $pic = '';
        if($_FILES['pic']['tmp_name']){
            $img = new mop_image;
            $result = $img->uploadAttachment('pic',1500,3000);
            if($result['code']!=100){
                return $this->output($result,false);
            }
            $pic = $result['data'];
        }
        $result = loadm('admin_location')->location_page_submit($id,$name,$pic);
        return $this->output($result,false);
    }
    /**
     * 删除某定位页面
     */
    public function location_page_del()
    {
        $id = (int)_gp('id');
        $result = loadm('admin_location')->location_page_del($id);
        return $this->output($result,false);
    }
}