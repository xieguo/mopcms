<?php
/**
 * 专题管理模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_archives_specials extends base_model
{
    /**
     * 专题模型字段列表页面
     * @param $sid 专题ID
     */
    public function fields_list($sid)
    {
        if(empty($sid)){
            return array();
        }
        $list = t('specials_fields')->fetchListLoop(array('sid'=>$sid),'','','displayorder');
        foreach ($list as &$v){
            if (strpos($v['title'], '#')) {
                list($v['title'],) = explode('#', $v['title']);
            }
        }
        return $list;
    }

    /**
     * 字段修改
     * @param $id 字段ID
     */
    public function fields_edit($id)
    {
        if (empty($id)){
            return array();
        }
        return t('specials_fields')->fetch($id);
    }

    /**
     * 字段添加修改保存
     * @param unknown_type $post
     */
    public function fields_save($post)
    {
        $data = array();
        $data['title'] = $post['title'];
        $data['datatype'] = $post['datatype'];
        if(empty($data['title']) || empty($data['datatype'])) {
            return getResult(10002);
        }
        $data['available'] = $post['available'];
        $data['unique'] = $post['unique'];
        $data['infront'] = $post['infront'];
        $data['required'] = $post['required'];
        $data['displayorder'] = $post['displayorder'];
        $data['checkrule'] = $post['checkrule'];
        $data['default'] = $post['default'];
        $data['intro'] = $post['intro'];
        $data['units'] = $post['units'];
        $data['tip'] = $post['tip'];
        $data['nullmsg'] = $post['nullmsg'];
        $data['errormsg'] = $post['errormsg'];
        $data['rules'] = $post['rules'];
        $data['maxlength'] = $post['maxlength'];
        $data['css'] = $post['css'];
        if(!empty($post['id'])) {
            t('specials_fields')->update($post['id'],$data);
            t('specials_fields')->lastUpdateTime();
            return getResult(100,'',10312);
        }else{
            $data['sid']  = $post['sid'];
            $num = $this->create_fields($data['sid']);
            if($num>35){
                return getResult(10418);
            }
            $data['fieldname']  = 'ext'.$num;
            if(t('specials_fields')->count(array('sid'=>$data['sid'],'fieldname'=>$data['fieldname']))){
                return getResult(10310);
            }
            $id = t('specials_fields')->insert($data,true);
            return getResult(100,$id,10313);
        }
    }

    /**
     * 生成一个变量名
     */
    private function create_fields($sid)
    {
        $row = t('specials_fields')->fetchList(array('sid'=>$sid),'fieldname',1);
        return (int)str_replace('ext','',aval($row,'0/fieldname'))+1;
    }

    /**
     * 更新排序
     */
    public function update_order($post)
    {
        if(empty($post['sid'])){
            return getResult(10001);
        }
        $sid = (int)$post['sid'];
        $row = t('specials_fields')->fetchList(array('sid'=>$sid), 'id');
        foreach($row as $v) {
            $displayorder = (int) $post['displayorder' . $v['id']];
            t('specials_fields')->update($v['id'], array('displayorder' => $displayorder));
        }
        t('specials_fields')->lastUpdateTime();
        return getResult(100, '',10202,'?mod=archives&ac=fields&do=list&sid='.$sid.'&rand=1');
    }

    /**
     * 专题报名数据列表
     */
    public function entry_list($post)
    {
        if(empty($post['sid'])){
            return array();
        }
        $_data = $post;
        $_data['title'] = t('archives')->fetchFields($post['sid'],'title');

        $where = array('sid'=>$post['sid']);
        if(!empty($post['words'])){
            $where[] = db::fbuild('CONCAT(ext1,ext2,ext3,ext4,ext5,ext6,ext7)',$post['words'],'like');
        }
        $_data['fieldlist'] = t('specials_fields')->fetchList(array('sid'=>$post['sid'],'available'=>1),'id,title,fieldname,datatype,rules',5,'displayorder');
        $fields = array('id','ischeck','createtime');
        foreach($_data['fieldlist'] as $k=>$v){
            if($v['datatype']=='imgs'){
                unset($_data['fieldlist'][$k]);
                continue;
            }
            if($v['datatype']=='multilevel' && strpos($v['title'],'#')){
                list($v['title'],) = explode('#',$v['title']);
            }
            $fields[] = $v['fieldname'];
            $_data['fieldlist'][$k] = $v;
        }
        $condition = array();
        $condition['where'] = $where;
        $condition['pagesize'] = 50;
        if($fields){
            $condition['fields'] = implode(',', $fields);
        }
        if(!empty($post['page'])){
            $condition['page'] = $post['page'];
        }
        $_data['result'] = t('specials_entry')->info_list($condition);
        $formdata = new mop_formdata;
        foreach ($_data['result']['list'] as $k=>$v){
            $formdata->get_value_from_db($_data['fieldlist'], $v);
            $_data['result']['list'][$k] = $v;
        }
        return $_data;
    }

    /**
     * 获取指定数量的数据
     * @param unknown_type $post
     */
    public function entryLimit($post)
    {
        if(empty($post['sid'])){
            return getResult(10001);
        }
        $limit = !empty($post['limit']) ? $post['limit'] : 10;
        $order = !empty($post['order']) ? $post['order'] : '';
        $sc = !empty($post['sc']) ? $post['sc'] : 'desc';
        $fields = !empty($post['fields']) ? $post['fields'] : '';

        $where = array();
        $where['sid'] = $post['sid'];
        if (!empty($post['ischeck'])) {
            $where['ischeck'] = $post['ischeck'];
        }
        if (!empty($post['mid'])) {
            $where['mid'] = $post['mid'];
        }
        if (!empty($post['where'])) {
            $where[] = $post['where'];
        }

        if($fields){
            $fieldlist = t('specials_fields')->fetchList(array('sid'=>$post['sid'],'available'=>1,'fieldname'=>explode(',', $fields)),'id,title,fieldname,datatype,rules','','displayorder');
        }else{
            $fieldlist = t('specials_fields')->fetchList(array('sid'=>$post['sid'],'available'=>1),'id,title,fieldname,datatype,rules','','displayorder');
        }
        
        $fields = array('id','ischeck','createtime');
        foreach($fieldlist as $k=>$v){
            if($v['datatype']=='imgs'){
                unset($fieldlist[$k]);
                continue;
            }
            if($v['datatype']=='multilevel' && strpos($v['title'],'#')){
                list($v['title'],) = explode('#',$v['title']);
            }
            $fields[] = $v['fieldname'];
            $fieldlist[$k] = $v;
        }
        $list = t('specials_entry')->fetchList($where, implode(',', $fields), $limit, $order, $sc);
        $formdata = new mop_formdata;
        foreach ($list as $k=>$v){
            $formdata->get_value_from_db($fieldlist, $v);
            $list[$k] = $v;
        }
        return getResult(100,$list);
    }

    /**
     * 报名页面
     * @param unknown_type $sid
     */
    public function entry($sid)
    {
        $result = $this->entry_check($sid);
        $_data = array('sid'=>$sid);
        $_data['msg'] = '';
        if($result['code'] != 100) {
            $_data['msg'] = $result['msg'];
        }
        $_data['arc'] = t('archives')->fetch($sid);
        $_data['specials'] = aval($result, 'data/row');
        $_data['count'] = (int)aval($result, 'data/count');
        return getResult(100,$_data);
    }

    /**
     * 报名前检测
     * @param unknown_type $sid
     */
    private function entry_check($sid)
    {
        if(empty($sid)){
            return getResult(10001);
        }
        $row = t('specials')->fetch($sid);
        if(empty($row)){
            return getResult(10004);
        }
        if($row['startdate']>TIME){
            return getResult(10415);
        }
        if ($row['enddate'] < TIME){
            return getResult(10416);
        }
        $count = t('specials_entry')->count(array('sid'=>$sid));
        if (!empty($row['maxnum']) && $row['maxnum'] <= $count){
            return getResult(10414);
        }
        return getResult(100,array('row'=>$row,'count'=>$count));
    }

    /**
     * 专题报名处理方法
     */
    public function entry_save($sid,$id='')
    {
        global $_M;
        $result = $this->entry_check($sid);
        if($result['code'] != 100) {
            return $result;
        }
        $ischeck = $result['data']['row']['auditstat']==2?2:1;
        //必填检测
        if(!$id){
            define('ATTACHCHECK',true);
        }
        $result = $this->checkRequiredFields($sid);
        if($result['code'] != 100) {
            return $result;
        }
        //唯一检测
        $result = $this->checkUniqueFields($sid,$id);
        if($result['code'] != 100) {
            return $result;
        }
        $result = t('specials_fields')->fetchFieldsValue($sid);
        if($result['code'] != 100) {
            return $result;
        }
        $data = $result['data'];
        if($id) {
            t('specials_entry')->update($id, $data);
            t('specials_entry')->lastUpdateTime();
            return getResult(100,'',10013);
        } else {
            $data['ischeck'] = $ischeck;
            $data['mid'] = $_M['mid'];
            $data['ip'] = $_M['ip'];
            $data['sid'] = $sid;
            $data['createtime'] = TIME;
            $id = t('specials_entry')->insert($data,true);
            return getResult(100,'',10417);
        }
    }

    /**
     * 必填字段查询
     * @param $sid 专题ID
     * @version 1.0
     */
    private function checkRequiredFields($sid)
    {
        if(empty($sid)) {
            return array();
        }
        $requireds = t('specials_fields')->fetchList(array('sid' => $sid, 'required' => 1, 'available' => 1), 'datatype,title,fieldname');
        $this->multilevel_change($requireds);
        return $this->_checkRequiredFields($requireds);
    }

    /**
     * 是否唯一检查
     * @param $sid 专题ID
     * @param $id
     */
    private function checkUniqueFields($sid,$id='')
    {
        if(empty($sid)) {
            return getResult(10001);
        }
        $uniques = t('specials_fields')->fetchList(array('sid' => $sid, 'unique' => 1, 'available' => 1), 'title,fieldname');
        if(empty($uniques)){
            return getResult(100);
        }
        $this->multilevel_change($uniques);
        foreach($uniques as $v) {
            $val = filterString(_gp($v['fieldname']));
            $num = $id && t('specials_entry')->count(array('id'=>$id,$v['fieldname']=>$val))?1:0;
            $count = t('specials_entry')->count(array('sid'=>$sid,$v['fieldname']=>$val))-$num;
            if($count>0) {
                return getResult(10020, '', array('title' => $val));
            }
        }
        return getResult(100);
    }

    /**
     * 某条专题报名数据详细
     * @param unknown_type $id
     */
    public function entry_by_id($id)
    {
        $res = t('specials_entry')->fetch($id);
        if(empty($res)){
            return array();
        }
        //如果开启会员插件
        $res['_mid'] = pluginIsAvailable('member')?t('member')->fetchFields($res['mid'],'username'):'';
        $res['fieldlist'] = t('specials_fields')->fetchList(array('sid'=>$res['sid'],'available'=>1),'title,fieldname,datatype,units,rules','','displayorder');
        foreach ($res['fieldlist'] as &$v){
            if (strpos($v['title'], '#')) {
                list($v['title'],) = explode('#', $v['title']);
            }
        }
        $formdata = new mop_formdata;
        $formdata->get_value_from_db($res['fieldlist'], $res);
        return $res;
    }

    /**
     * 删除某条报名数据
     * @param unknown_type $id
     */
    public function entry_del($id)
    {
        $res = t('specials_entry')->fetch($id);
        if(empty($res)){
            return array();
        }
        $fieldlist = t('specials_fields')->fetchList(array('sid'=>$res['sid'],'available'=>1,'datatype'=>array('img','imgs','media','addon')));
        foreach ($fieldlist as $v){
            if($v['datatype']=='imgs') {
                foreach(unserialize($res[$v['fieldname']]) as $p) {
                    $this->_del_image($p['img']);
                }
            }else{
                $this->_del_image($res[$v['fieldname']]);
            }
        }
        return t('specials_entry')->delete($id);
    }

    /**
     * 报名审核
     */
    public function entry_audit($id,$ischeck)
    {
        if(empty($id)){
            return getResult(10001);
        }
        $ischeck = $ischeck==1?1:2;
        t('specials_entry')->update($id,array('ischeck'=>$ischeck));
        t('specials_entry')->lastUpdateTime();
        return getResult(100);
    }

    /**
     * 专题报名数据导出
     * @param unknown_type $sid
     */
    public function entry_export($sid)
    {
        $fieldlist = t('specials_fields')->fetchList(array('sid'=>$sid,'available'=>1),'title,fieldname,datatype,units,rules','','displayorder');
        $fields = array('id','ip','createtime','mid','ischeck');
        foreach ($fieldlist as $v) {
            $fields[] = $v['fieldname'];
        }
        $list = t('specials_entry')->fetchListLoop(array('sid'=>$sid), implode(',', $fields));
        return $this->export($sid,$fieldlist,$list);
    }
}