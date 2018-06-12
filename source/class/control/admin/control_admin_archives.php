<?php
/**
 * 后台文档管理控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_archives extends control_admin
{
    /**
     * 输出数据
     * @param $result 数据
     * @param $modelid 模型ID
     * @param $referer 跳转URL
     */
    protected function output($result='',$modelid='',$referer='')
    {
        global $_M;
        $template = false;
        if($modelid!==false){
            $template = $this->archives_temp($modelid);
        }
        $result = parent::output($result,$template,$referer);
        if($result['code']==100){
            $result['referer'] = $_M['cururl'] . '&ac=list&id=&menuid=38';
        }
        return $result;
    }

    /**
     * 如果模型对应文档模板不存在，就调用默认模板
     */
    private function archives_temp($modelid='')
    {
        global $_M;
        if(empty($modelid)) {
            $modelid = 1;
        }
        $tmp = $_M['ac'] . '_' . $modelid;
        if(!is_file(MOPTEMPLATE . CURSCRIPT . '/' . $_M['mod'] . '/' . $tmp . '.htm')) {
            $tmp = $_M['mod'] . '_' . $_M['ac'];
        }
        return $tmp;
    }

    /**
     * 文档列表页面
     */
    public function archives_list()
    {
        require_once loadlib('archives');
        $post = array();
        $post['modelid'] = _gp('modelid') ? (int) _gp('modelid') : 1;
        if(!empty($this->admin['_groupid']['_mpurviews']) && !in_array($post['modelid'],$this->admin['_groupid']['_mpurviews'])){
            return getResult(10103);
        }
        $post['words'] = filterString(_gp('words'));
        $columnid = (int)_gp('columnid');
        $post['columnid'] = 0;
        //如果是专题没有栏目的概念
        if($post['modelid']!='-1' && $columnid){
            $result = $this->column_allow_check($columnid);
            if($result['code']!=100){
                return $result;
            }
            $post['columnid'] = $columnid;
        }
        $post['mycolumnid'] = !empty($this->admin['_columnids'])?$this->admin['_columnids']:'';
        $post['flag'] = preg_replace('/[^\w]/i','',_gp('flag'));
        $post['order'] = filterString(_gp('order'));
        $post['username'] = filterString(_gp('username'));
        $post['adminid'] = (int)_gp('adminid');
        $post['mid'] = (int) _gp('mid');
        $this->cururl($post);
        $post['pagesize'] = 50;
        $result = loadm('admin_archives')->archives_list($post);
        return $this->output($result, $post['modelid']);
    }

    /**
     * 文档编辑页面
     */
    public function archives_edit()
    {
        $id = (int) _gp('id');
        $result = $this->archive_allow_check($id);
        if($result['code']!=100){
            return $result;
        }
        $result = loadm('admin_archives')->archives_edit($id);
        if(!empty($this->admin['_groupid']['_mpurviews']) && !in_array($result['data']['modelid'],$this->admin['_groupid']['_mpurviews'])){
            return getResult(10103);
        }
        return $this->output($result, $result['data']['modelid']);
    }
    /**
     * 文档编辑处理
     */
    public function archives_edit_submit()
    {
        return $this->archives_save();
    }
    private function archives_save()
    {
        $id = (int) _gp('id');
        $columnid = (int) _gp('columnid');
        $result = $this->archive_allow_check($id);
        if($result['code']!=100){
            return $result;
        }
        $result = t('archives')->save($id);
        return $this->output($result,false);
    }
    /**
     * 文档添加页面
     */
    public function archives_add()
    {
        $modelid = (int) _gp('modelid');
        $columnid = (int) _gp('columnid');
        if($columnid){
            $modelid = $_GET['modelid'] = t('column')->fetchFields($columnid,'modelid');
        }
        if(!empty($this->admin['_groupid']['_mpurviews']) && !in_array($modelid,$this->admin['_groupid']['_mpurviews'])){
            return getResult(10103);
        }
        $result = loadm('admin_archives')->archives_add($modelid);
        $result['data']['columnid'] = $columnid;
        return $this->output($result, $modelid);
    }
    /**
     * 文档添加处理
     */
    public function archives_add_submit()
    {
        return $this->archives_save();
    }

    /**
     * 删除文档（进回收站）
     */
    public function archives_del()
    {
        global $_M;
        $result = $this->valid_ids();
        if($result['code']!=100){
            return $result;
        }
        $ids = $result['data'];
        if($_M['sys']['delete'] == 1) {
            $result = loadm('archives')->update_status($ids);
        }else{
            $result = loadm('archives')->archives_del($ids);
        }
        return $this->output($result,false);
    }

    /**
     * 获取有效的id
     */
    protected function valid_ids()
    {
        $ids = parent::valid_ids();
        $result = $this->archive_allow_check($ids);
        if($result['code']!=100){
            return $result;
        }
        return getResult(100,$ids);
    }

    /**
     * 上传缩略图
     */
    public function archives_add_uploadlitpic()
    {
        $img = new mop_image;
        $result = $img->uploadAttachment('thumb');
        if ($result['code']!=100) {
            $msg = "<script language='javascript'>alert('".$result['msg']."');</script>";
        } else {
            $msg = "<script language='javascript'>" .
                    "parent.document.getElementById('upload_text').innerHTML=\"上传图片\";
                        parent.document.getElementById('picname').value = '".$result['data']."';
                        if(parent.document.getElementById('divpicview')){
                            parent.document.getElementById('divpicview').style.width = '150px';
                            parent.document.getElementById('divpicview').innerHTML = \"<img src='".imageResize($result['data'],150,150)."' width='150' id='picview' name='picview'/>\";
                        }
                    </script>";
        }
        echo $msg;
        exit();
    }

    /**
     * 图片剪切页面
     */
    public function archives_imagecut()
    {
        $_data = array();
        $_data['pic'] = filterString(_gp('pic'));
        return $this->output($_data);
    }

    public function archives_imagecut_mng()
    {
        $img = new mop_image;
        $result = $img->uploadAttachment('pic');
        return $this->output($result,false);
    }

    /**
     * 文档审核
     */
    public function archives_check()
    {
        $status = (int)_gp('status');
        $status = $status==1?1:($status==-1?-1:0);
        $result = $this->valid_ids();
        if($result['code']!=100){
            return $result;
        }
        $ids = $result['data'];
        $result = loadm('archives')->archives_check($ids,$status);
        return $this->output($result,false);
    }

    /**
     * 推荐
     */
    public function archives_recommend()
    {
        $result = $this->valid_ids();
        if($result['code']!=100){
            return $result;
        }
        $ids = $result['data'];
        $result = loadm('archives')->archives_recommend($ids);
        return $this->output($result,false);
    }

    /**
     * 生成静态文件
     */
    public function archives_makehtml()
    {
        $result = $this->valid_ids();
        if($result['code']!=100){
            return $result;
        }
        $ids = $result['data'];
        $result = loadm('archives')->archives_makehtml($ids);
        return $this->output($result,false);
    }

    /**
     * 预览
     */
    public function archives_view()
    {
        $id = (int)_gp('id');
        $link = t('archives')->getHtmlLink($id);
        header('location:'.$link);
        exit;
    }

    /**
     * 定位信息管理
     */
    public function archives_point()
    {
        $pageid = (int) _gp('pageid');
        $id = (int) _gp('id');
        $result = controlCache('admin_location','location_point',$pageid,'location_points');
        $result['data']['aid'] = $id;
        $result['data']['arc'] = loadm('archives')->fetch_by_id($id);
        return $this->output($result);
    }

    /**
     * 定位点信息添加修改
     */
    public function archives_point_submit()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['title'] = filterString(_gp('title'));
        $post['color'] = filterString(_gp('color'));
        $post['size'] = (int) _gp('size');
        $post['isbold'] = _gp('isbold') ? 1 : 0;
        $post['aid'] = (int) _gp('aid');
        $post['pointid'] = (int) _gp('pointid');
        $post['summary'] = filterString(_gp('summary'));
        $post['thumb'] = filterString(_gp('picname'));
        $post['displayorder'] = (int) _gp('displayorder');
        $post['adminid'] = $_M['admin']['id'];
        return loadm('admin_location')->location_infolist_save($post);
    }

    /**
     * 专题报名字段列表页面
     */
    public function archives_fields_list()
    {
        $sid = (int)_gp('sid');
        $_data = array('sid'=>$sid);
        $_data['arc'] = loadm('archives')->fetch_by_id($sid);
        if(empty($_data['arc'])){
            return getResult(10004);
        }
        $_data['list'] = controlCache('archives_specials','fields_list',$sid,'specials_fields');
        return parent::output($_data);
    }
    /**
     * 专题报名字段排序操作
     */
    public function archives_fields_list_submit()
    {
        $result = loadm('archives_specials')->update_order($_GET);
        return parent::output($result,false);
    }

    /**
     * 专题报名字段修改页面
     */
    public function archives_fields_edit()
    {
        $sid = (int)_gp('sid');
        $id = (int)_gp('id');
        $_data = array('sid'=>$sid);
        $_data['arc'] = loadm('archives')->fetch_by_id($sid);
        if(empty($_data['arc'])){
            return getResult(10004);
        }
        $_data['row'] = loadm('archives_specials')->fields_edit($id);
        return parent::output($_data);
    }
    public function archives_fields_edit_submit()
    {
        return $this->_fields_save();
    }
    private function _fields_save()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['title'] = filterString(_gp('title'));
        $post['datatype'] = array_key_exists(_gp('datatype'), $_M['sys']['datatype']) ? _gp('datatype') : '';
        $post['available'] = (int) _gp('available');
        $post['unique'] = (int) _gp('unique');
        $post['infront'] = (int) _gp('infront');
        $post['required'] = (int) _gp('required');
        $post['displayorder'] = (int) _gp('displayorder');
        $post['checkrule'] = _gp('checkrule');
        $post['default'] = filterString(_gp('default'));
        $post['intro'] = filterString(_gp('intro'));
        $post['units'] = filterString(_gp('units'));
        $post['tip'] = filterString(_gp('tip'));
        $post['nullmsg'] = filterString(_gp('nullmsg'));
        $post['errormsg'] = filterString(_gp('errormsg'));
        $post['rules'] = serializeData(_gp('rules'));
        $post['maxlength'] = (int) _gp('maxlength');
        $post['css'] = preg_replace('/[^\w ]/i', '', _gp('css'));
        $post['sid'] = (int) _gp('sid');
        $result = loadm('archives_specials')->fields_save($post);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return parent::output($result,false);
    }

    /**
     * 专题报名字段添加页面
     */
    public function archives_fields_add()
    {
        $sid = (int)_gp('sid');
        $_data = array('sid'=>$sid);
        $_data['arc'] = loadm('archives')->fetch_by_id($sid);
        if(empty($_data['arc'])){
            return getResult(10004);
        }
        return parent::output($_data);
    }
    public function archives_fields_add_submit()
    {
        return $this->_fields_save();
    }

    /**
     * 专题报名删除某字段
     */
    public function archives_fields_del()
    {
        global $_M;
        $id = (int)_gp('id');
        t('specials_fields')->delete($id);
        $result = getResult(100, '',10314,$_M['cururl'] . '&do=list&id=');
        return parent::output($result,false);
    }

    /**
     * 专题报名列表
     */
    public function archives_entry()
    {
        global $_M;
        $post = array();
        $post['sid'] = (int)_gp('sid');
        $post['words'] = filterString(_gp('words'));
        $this->cururl($post);
        $post['page'] = $_M['page'];
        $result = controlCache('archives_specials','entry_list',$post,'specials_entry');
        //计算底部操作需要占用的td数
        $result['colspan'] = count($result['fieldlist']);
        $result['colspan'] += 3;
        return parent::output($result);
    }

    /**
     * 某条报名数据详细
     */
    public function archives_entry_view()
    {
        $id = (int)_gp('id');
        $result = loadm('archives_specials')->entry_by_id($id);
        if (empty($result)){
            return getResult(10004);
        }
        return parent::output($result,false);
    }

    /**
     * 报名审核
     */
    public function archives_entry_check()
    {
        $id = (int)_gp('id');
        $ischeck = (int)_gp('ischeck');
        $result = loadm('archives_specials')->entry_audit($id,$ischeck);
        return parent::output($result,false);
    }

    /**
     * 删除某条报名数据
     */
    public function archives_entry_del()
    {
        $id = (int)_gp('id');
        $result = loadm('archives_specials')->entry_del($id);
        return parent::output($result,false);
    }

    /**
     * 专题报名数据导出
     */
    public function archives_entry_export()
    {
        $sid = (int)_gp('sid');
        echo loadm('archives_specials')->entry_export($sid);
        exit;
    }

    /**
     * 评论列表
     */
    public function archives_comments()
    {
        global $_M;
        $post = array();
        $post['manage'] = 1;
        $post['words'] = filterString(_gp('words'));
        $post['page'] = $_M['page'];
        $this->cururl($post);
        $para = $post;
        if(is_numeric($post['words'])){
            $post['aid'] = $post['words'];
            unset($post['words']);
        }
        $result = controlCache('main_comments','info_list',$post,'archives_comment');
        $result['data'] += $para;
        return parent::output($result);
    }

    /**
     * 报名审核
     */
    public function archives_comments_check()
    {
        $id = preg_replace('/[^\d,]/', '', _gp('id'));
        $ischeck = (int)_gp('ischeck');
        $result = loadm('main_comments')->comments_check($id,$ischeck);
        return parent::output($result,false);
    }

    /**
     * 删除某条报名数据
     */
    public function archives_comments_del()
    {
        $id = preg_replace('/[^\d,]/', '', _gp('id'));
        $result = loadm('main_comments')->comments_del($id);
        return parent::output($result,false);
    }
}