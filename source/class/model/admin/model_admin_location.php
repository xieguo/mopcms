<?php
/**
 * 后台定位点模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_location extends base_model
{
    /**
     * 定位点展示页面
     */
    public function location_point($pageid)
    {
        global $_M;
        if(empty($pageid)){
            return getResult(10001);
        }
        $_data = array();
        loadcache('locationpage');
        $_data['row'] = $_M['cache']['locationpage'][$pageid];
        if(empty($_data['row'])) {
            return getResult(10004);
        }
        $_data['points'] = t('location_points')->fetchList(array('pageid' => $pageid));
        return getResult(100,$_data);
    }

    /**
     * 定位点处理操作
     */
    public function location_point_submit($id='',$post)
    {
        if(empty($post)){
            return getResult(10001);
        }
        if(empty($post['coords'])){
            return getResult(10413);
        }
        $post['infonums'] = max(1,$post['infonums']);
        if(empty($id)){
            $id = t('location_points')->fetchFields(array('coords'=>$post['coords']),'id');
        }

        if($id){
            t('location_points')->update($id,$post);
            t('location_points')->lastUpdateTime();
        }else{
            $id = t('location_points')->insert($post,true);
        }
        return getResult(100,$id);
    }

    /**
     * 某定位点信息
     * @param unknown_type $id
     */
    public function location_point_info($id)
    {
        if(empty($id)){
            return getResult(10001);
        }
        $point = t('location_points')->fetch($id);
        return getResult(100,$point);
    }

    /**
     * 删除定位点
     */
    public function location_point_del($id)
    {
        if(empty($id)){
            return getResult(10001);
        }
        t('location_points')->delete($id);
        $list = t('location_infolist')->fetchList(array('pointid'=>$id));
        foreach($list as $v){
            if(!empty($v['thumb'])){
                $this->_del_image($v['thumb']);
            }
        }
        t('location_infolist')->delete(array('pointid'=>$id));
        return getResult(100);
    }

    /**
     * 修改定位点坐标
     */
    public function location_point_editcoord($id,$coords)
    {
        if(empty($id)||empty($coords)){
            return getResult(10001);
        }
        t('location_points')->update($id, array('coords' => $coords));
        t('location_points')->lastUpdateTime();
        return getResult(100);
    }

    /**
     * 定位页面展示
     */
    public function location_page()
    {
        global $_M;
        $_data = array();
        $_data['template'] = 'page';
        loadcache('locationpage');
        $list = $_M['cache']['locationpage'];
        $_data['list'] = array();
        foreach($list as $k=>$v){
            if($v){
                $v['pointnum'] = t('location_points')->count(array('pageid'=>$k));
                $_data['list'][$k] = $v;
            }
        }
        return getResult(100,$_data);
    }

    /**
     * 定位页面添加修改
     * @param $key KEY值
     * @param $name 定位页面名称
     * @param $pic 定位图片
     */
    public function location_page_submit($key,$name,$pic)
    {
        if(empty($name)){
            return getResult(10001);
        }
        global $_M;
        loadcache('locationpage');
        $list = $_M['cache']['locationpage'];
        $data = array();
        $data['name'] = $name;
        $data['pic'] = $pic;
        if($key){
            if(!empty($data['pic'])){
                $this->_del_image($list[$key]['pic']);
            }else{
                $data['pic'] = $list[$key]['pic'];
            }
            $code = 10013;
        }else{
            if(empty($data['pic'])){
                return getResult(10002);
            }
            $key = empty($list)?1:(count($list)+1);
            $code = 10014;
        }
        $list[$key] = $data;
        t('sys_cache')->save('locationpage', $list);
        return getResult(100,'',$code);
    }

    /**
     * 删除某定位页面
     * @param unknown_type $key
     */
    public function location_page_del($key)
    {
        global $_M;
        if(empty($key)){
            return getResult(10001);
        }
        loadcache('locationpage');
        $list = $_M['cache']['locationpage'];
        if(!empty($list[$key]['pic'])){
            $this->_del_image($list[$key]['pic']);
        }
        $list[$key] = array();
        t('sys_cache')->save('locationpage', $list);
        return getResult(100,'',10016);
    }

    public function location_infolist_id($id)
    {
        return t('location_infolist')->fetch($id);
    }

    /**
     * 某定位点信息列表
     * @param unknown_type $post
     */
    public function location_infolist($post)
    {
        if(empty($post)){
            return getResult(10001);
        }
        if(is_numeric($post)){
            $pointid = $post;
        }else{
            $pointid = aval($post, 'pointid');
        }
        if(empty($pointid)){
            return getResult(10001);
        }
        $_data = array();
        $_data['point'] = t('location_points')->fetch($pointid);
        if(empty($_data['point'])){
            return getResult(10004);
        }
        $limit = !empty($post['limit'])?$post['limit']:$_data['point']['infonums'];
        $order = !empty($post['order']) ? $post['order'] : 'displayorder desc,id';
        $sc = !empty($post['sc']) ? $post['sc'] : 'desc';
        $_data['list'] = t('location_infolist')->fetchListLoop(array('pointid'=>$pointid),'*',$limit,$order,$sc);
        return getResult(100,$_data);
    }

    /**
     * 定位点信息添加修改
     * @param $post
     */
    public function location_infolist_save($post)
    {
        $data = array();
        $data['title'] = $post['title'];
        if(empty($data['title'])) {
            return getResult(10002);
        }
        $data['color'] = $post['color'];
        $data['size'] = $post['size'];
        $data['isbold'] = $post['isbold'] ? 1 : 0;
        $data['thumb'] = $post['thumb'];
        $data['aid'] = $post['aid'];
        if(empty($data['aid'])) {
            return getResult(10001);
        }
        $data['pointid'] = $post['pointid'];
        if(!empty($post['id'])) {
            $data['pointid'] = t('location_infolist')->fetchFields($post['id'], 'pointid');
            if(empty($data['pointid'])){
                return getResult(10004);
            }
        }
        if(empty($data['pointid'])) {
            return getResult(10001);
        }
        if(empty($post['id'])) {
            $post['id'] = t('location_infolist')->fetchFields(array('pointid' => $data['pointid'], 'aid' => $data['aid']), 'id');
        }
        $point = t('location_points')->fetch($data['pointid']);
        $data['summary'] = $post['summary'];
        $data['displayorder'] = $post['displayorder'];
        if($point['imgwidth'] && $point['imgheight'] && empty($data['thumb'])) {
            $thumb = t('archives')->fetchFields($data['aid'],'thumb');
            if(!preg_match('/https?:\/\//i', $thumb)){
                $info = pathinfo($thumb);
                if(!empty($info['basename'])){
                    $data['thumb'] = $info['dirname'].'/'.random(8).$info['basename'];
                    copy(MOPROOT.$thumb, MOPROOT.$data['thumb']);
                    $img = new mop_image;
                    $img->imageResize(MOPROOT.$data['thumb'], $point['imgwidth'], $point['imgheight']);
                }
            }
        }
        if(!empty($post['id'])) {
            unset($data['pointid']);
            if(empty($data['thumb'])) {
                unset($data['thumb']);
            } else {
                $thumb = t('location_infolist')->fetchFields($post['id'], 'thumb');
                if($thumb!=$data['thumb']){
                    $this->_del_image($thumb);
                }
            }
            t('location_infolist')->update($post['id'], $data);
            t('location_infolist')->lastUpdateTime();
            return getResult(100,'',10013);
        } else {
            $data['createtime'] = TIME;
            $data['adminid'] = $post['adminid'];
            t('location_infolist')->insert($data);
            return getResult(100,'',10014);
        }

    }
}