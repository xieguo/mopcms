<?php
/**
 * 文档模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_archives extends base_model
{
    public function fetch_by_id($id)
    {
        return t('archives')->fetch($id);
    }

    /**
     * 文档删除操作
     * @param unknown_type $id
     */
    public function archives_del($id)
    {
        global $_M;
        if(empty($id)) {
            return getResult(10006);
        }
        if(is_array($id)) {
            foreach($id as $v) {
                $this->archives_del($v);
            }
        } else {
            $arc = archiveInfo($id,'*');

            //是否删除文档相应附件
            if($_M['sys']['upload_switch'] == 1) {
                if($fields = t('models_fields')->fetchList(array('modelid' => $arc['modelid']), 'fieldname,datatype')) {
                    foreach($fields as $v) {
                        $data = $arc['attach'][$v['fieldname']];
                        if(empty($data)) {
                            continue;
                        }
                        switch($v['datatype']) {
                            case 'htmltext' :
                                $tmpname = '/(\\' . $_M['sys']['medias_dir'] . '.+?)(\"|\|| )/';
                                preg_match_all($tmpname, mstripslashes($data) . ' ', $delname);
                                $delname = array_unique($delname['1']);
                                foreach($delname as $var) {
                                    $this->_del_image($var);
                                }
                                break;
                            case 'imgs' :
                                foreach(unserialize($data) as $p) {
                                    $this->_del_image($p['img']);
                                }
                                break;
                            case 'media' :
                            case 'addon' :
                            case 'img' :
                                $this->_del_image($data);
                                break;
                        }
                    }
                }
                if($arc['modelid'] == -1) {
                    t('specials_entry')->delete(array('sid' => $id));
                    t('specials_fields')->delete(array('sid' => $id));
                }
                $this->_del_image($arc['thumb']);
            }

            t('archives')->delete($id);
            t('archives_comment')->delete(array('aid' => $id));
            if(!empty($arc['model']['tablename'])) {
                t($arc['model']['tablename'])->delete($id);
            }

            //删除定位的相应信息
            $this->_del_pointinfo(array('aid' => $id));
            //删除HTML
            $this->_delhtml($arc['htmllink']);
            
            t('archives')->lastUpdateTime();
        }
        return getResult(100,'',10016);
    }

    /**
     * 修改某文档的状态
     * @param unknown_type $id
     * @param unknown_type $status
     */
    public function update_status($id,$status=-1)
    {
        if(empty($id)) {
            return getResult(10006);
        }
        if(!in_array($status, array(-1,0,1))){
            return getResult(10001);
        }
        $condition = $id;
        if(is_array($id)){
            $condition = array('id'=>$id);
        }
        t('archives')->update($condition,array('status'=>$status));
        $code = 10013;
        if($status==-1){
            $code = 10016;
            //删除定位点上的对应信息
            $this->_del_pointinfo(array('aid' => $id));

            //删除HTML
            $list = t('archives')->fetchList(array('id'=>$id),'htmllink');
            foreach ($list as $v){
                $this->_delhtml($v['htmllink']);
            }
        }
        t('archives')->lastUpdateTime();
        return getResult(100,'',$code);
    }

    /**
     * 删除HTML
     * @param $htmllink HTML文件URL
     */
    private function _delhtml($htmlurl)
    {
        $htmlfile = MOPROOT . $htmlurl;
        if(is_file($htmlfile)) {
            @unlink($htmlfile);
            list($fname,$ext) = explode('.', $htmlfile);
            for($i = 2; $i <= 100; $i++) {
                $htmlfile = $fname . '_'.$i . '.' . $ext;
                if(is_file($htmlfile)) {
                    @unlink($htmlfile);
                }else{
                    break;
                }
            }
        }
        return getResult(100);
    }

    /**
     * 删除定位点信息
     * @param unknown_type $id
     */
    public function del_pointinfo($id)
    {
        return $this->_del_pointinfo($id);
    }
    /**
     * 删除定位点信息
     * @param unknown_type $id
     */
    private function _del_pointinfo($id)
    {
        if(empty($id)){
            return getResult(10001);
        }
        if(is_numeric($id)){
            $where = array('id' => $id);
        }else{
            $where = $id;
        }
        $row = t('location_infolist')->fetchList($where,'thumb');
        foreach($row as $v){
            if($v['thumb']){
                $this->_del_image($v['thumb']);
            }
        }
        t('location_infolist')->delete($where);
        return getResult(100);
    }

    /**
     * 文档审核操作
     * @param unknown_type $ids
     * @param unknown_type $status
     */
    public function archives_check($ids,$status)
    {
        if(empty($ids)) {
            return getResult(10006);
        }
        t('archives')->update(array('id'=>$ids),array('status'=>$status));
        t('archives')->lastUpdateTime();
        $codes = array(-1=>10410,0=>10409,1=>10408);
        return getResult(100,'',$codes[$status]);
    }

    /**
     * 文档推荐操作
     * @param unknown_type $ids
     */
    public function archives_recommend($ids)
    {
        if(empty($ids)) {
            return getResult(10006);
        }
        foreach((array)$ids as $id){
            $flag = t('archives')->fetchFields($id,'flag');
            $flags = $flag?explode(',',$flag):array();
            $flags[] = 'c';
            $flags = array_unique($flags);
            t('archives')->update($id,array('flag'=>implode(',',$flags)));
            t('archives')->lastUpdateTime();
        }
        return getResult(100,'',10411);
    }

    /**
     * 生成静态页面操作
     * @param unknown_type $ids
     */
    public function archives_makehtml($ids)
    {
        if(empty($ids)) {
            return getResult(10001);
        }
        foreach((array)$ids as $id){
            $arc = new archive_view($id);
            $arc->create_html();
        }
        return getResult(100,'',10412);
    }

    /**
     * 文档顶踩访问
     * @param unknown_type $id
     * @param unknown_type $op
     */
    public function dcviews($id,$type='')
    {
        if(empty($id)){
            return getResult(10001);
        }
        if($type){
            if(get_cookie('cookiedc'.$id)){
                return getResult(10019);
            }
            set_cookie('cookiedc'.$id,TIME,600);
            $type = $type=='cai'?'cai':'ding';
            t('archives')->updateInc($id,$type);
        }else{
            if(!get_cookie('cookieviews'.$id)){
                set_cookie('cookieviews'.$id,TIME,600);
                t('archives')->updateInc($id,'views');
            }
        }
        $row = t('archives')->fetchFields($id,'views,ding,cai');
        return getResult(100,$row);
    }

    /**
     * 必填字段检查
     */
    public function checkRequiredFields($requireds)
    {
        return $this->_checkRequiredFields($requireds);
    }

    /**
     * AJAX获取文档列表数据
     */
    public function list_ajaxdata($post)
    {
        global $_M;
        $result = array();
        if(empty($post['columnid']) && empty($post['words'])){
            return getResult(100,$result);
        }
        if(!empty($post['columnid'])){
            $post['modelid'] = !empty($post['columnid'])?t('column')->fetchFields($post['columnid'],'modelid'):'';
            $list = t('models_fields')->fetchSearchCondition($post['modelid']);
            if(!empty($post['time'])){
                $list['where'][] = db :: fbuild('createtime', $post['time'], '>');
            }
            if(!empty($list['where'])){
                $post['where'] = t('archives')->buildWhere($list['where']);
            }
        }
        $post['count'] = 1;
        $total = t('archives')->archivesList($post);
        $maxpages = max(1, ceil($total / $post['pagesize']));
        unset($post['count']);
        if($post['page']<=$maxpages){
            $post['fields'] = !empty($post['modelid'])?t('models_fields')->getInlistFields($post['modelid']):'';
            $res = t('archives')->archivesList($post);
            foreach ($res as $k=>$v){
                $result[$k]['id'] = $v['id'];
                $result[$k]['title'] = $v['title'];
                $result[$k]['description'] = $v['description'];
                $result[$k]['link'] = pseudoUrl($_M['sys']['cmspath'].'main.php?mod=archives&ac=view&id='.$v['id']);
                $result[$k]['thumb_exist'] = !empty($v['thumb']);
                if($v['modelid']==2){
                    $result[$k]['thumb'] = imageResize($v['thumb'],640,640);
                }else{
                    $result[$k]['thumb'] = imageResize($v['thumb'],100,100);
                }
                $result[$k]['attach'] = aval($v, 'attach');
            }
        }
        return getResult(100,$result,'',TIME);
    }

    /**
     *文档数量统计
     */
    public function archives_statistic($comments=false)
    {
        $list = t('models')->fetchList(array('enable'=>1),'id,modelname,tablename');
        foreach ($list as &$v){
            $v['count'] = t($v['tablename'])->count();
        }
        if ($comments===true){
            $list[] = array('modelname'=>'文档评论','count'=>t('archives_comment')->count());
        }
        return getResult(100,$list);
    }
}