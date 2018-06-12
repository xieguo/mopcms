<?php
/**
 * 自定义表单模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_main_customform extends base_model
{
    /**
     * 模型修改页面
     */
    public function customform_edit($formid)
    {
        if(empty($formid)){
            return getResult(10006);
        }
        $_data = array();
        $_data['formid'] = $formid;
        $_data['row'] = t('customform')->fetch($formid);
        if(empty($_data['row'])){
            return getResult(10303);
        }
        return getResult(100,$_data);
    }
    /**
     * 模型修改操作
     */
    public function customform_save($post)
    {
        $data = array();
        $data['formname'] = $post['formname'];
        if(empty($data['formname'])) {
            return getResult(10317);
        }
        $data['enable'] = $post['enable'];
        $data['frontpub'] = $post['frontpub'];
        $data['frontshow'] = $post['frontshow'];
        $data['needcheck'] = $post['needcheck'];
        $data['export'] = $post['export'];
        $data['intro'] = $post['intro'];
        if(!empty($post['id'])) {
            t('customform')->update($post['id'], $data);
            t('customform')->lastUpdateTime();
            return getResult(100, '',10304);
        } else {
            $data['tablename'] = $post['tablename'];
            if(empty($data['tablename'])) {
                return getResult(10318);
            }
            if(t('customform')->count(array('tablename' => $data['tablename']))) {
                return getResult(10306);
            }
            //创建表
            if(!t('customform')->createTable($data['tablename'])) {
                return getResult(10307);
            }
            $id = t('customform')->insert($data, true);
            return getResult(100, $id,10319);
        }
    }

    /**
     * 模型字段列表页面
     */
    public function customform_fields_list($formid)
    {
        $result = $this->_customform_fields($formid);
        if($result['code']!=100){
            return $result;
        }
        $_data = $result['data'];
        $_data['list'] = t('customform_fields')->fetchListLoop(array('formid'=>$formid),'','','displayorder');
        return getResult(100,$_data);
    }

    /**
     * 字段修改
     * @param $formid 模型ID
     * @param $id 字段ID
     */
    public function customform_fields_edit($formid,$id)
    {
        if(empty($id)){
            return getResult(10006);
        }
        $result = $this->_customform_fields($formid);
        if($result['code']!=100){
            return $result;
        }
        $_data = $result['data'];
        $_data['id'] = $id;
        $row = t('customform_fields')->fetch($id);
        if(empty($row)){
            return getResult(10004);
        }
        $_data['row'] = $row;
        return getResult(100,$_data);
    }
    public function customform_fields_save($post)
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
        $data['search'] = $post['search'];
        $data['joinsorting'] = $post['joinsorting'];
        $data['inlist'] = $post['inlist'];
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
            $row = t('customform_fields')->fetch($post['id']);
            if(empty($row)) {
                return getResult(10004);
            }
            t('customform_fields')->update($post['id'], $data);
            t('customform_fields')->lastUpdateTime();
            $this->_field_update($row['formid'],$row['fieldname'],$data);
            return getResult(100,'',10312);
        } else {
            $data['formid'] = $post['formid'];
            $data['fieldname'] = $post['fieldname'];
            $result = t('customform_fields')->fieldCheck($data['formid'], $data['fieldname']);
            if($result['code'] != 100) {
                return $result;
            }
            t('customform_fields')->insert($data);
            $this->_field_update($data['formid'],$data['fieldname'],$data);
            return getResult(100,'',10313);
        }
    }
    /**
     * 数据表处理
     * @param $formid 模型ID
     * @param $fieldname 字段名
     * @param $data 数据
     */
    private function _field_update($formid,$fieldname,$data)
    {
        $tablename = t('customform')->fetchFields($formid, 'tablename');
        t('customform')->createTable($tablename);
        $formdata = new mop_formdata($tablename);
        $formdata->field_update($fieldname, $data);
    }

    /**
     * 字段添加
     * @param $formid 模型ID
     */
    public function customform_fields_add($formid)
    {
        return $this->_customform_fields($formid);
    }

    private function _customform_fields($formid)
    {
        if(empty($formid)){
            return getResult(10001);
        }
        $_data = array();
        $_data['formid'] = $formid;
        $_data['form'] = t('customform')->fetch($formid);
        if(empty($_data['form'])){
            return getResult(10004);
        }
        return getResult(100,$_data);
    }

    /**
     * 必填字段检查
     */
    public function checkRequiredFields($formid)
    {
        if(empty($formid)) {
            return array();
        }
        $requireds = t('customform_fields')->fetchList(array('formid' => $formid, 'required' => 1, 'available' => 1), 'datatype,title,fieldname');
        $this->multilevel_change($requireds);
        return $this->_checkRequiredFields($requireds);
    }

    /**
     * 获取某自定义表单的详细信息
     * @param $formid 表单ID
     * @param $ac pub发布权限判断，show前台展示权限判断
     */
    public function fetch_by_formid($formid,$ac='')
    {
        return $this->_fetch_by_formid($formid,$ac);
    }
    private function _fetch_by_formid($formid,$ac='')
    {
        if (empty($formid)){
            return getResult(10001);
        }
        $row = t('customform')->fetch($formid);
        if (empty($row)||!$row['enable']){
            return getResult(10004);
        }
        if($ac=='pub' && empty($row['frontpub']) && !defined('ISMANAGE')){
            return getResult(10321);
        }
        if($ac=='show' && empty($row['frontshow']) && !defined('ISMANAGE')){
            return getResult(10322);
        }
        return getResult(100,array('row'=>$row,'formid'=>$formid));
    }

    /**
     * 自定义表单提交处理方法
     */
    public function save($formid,$id='')
    {
        global $_M;
        $result = $this->_fetch_by_formid($formid,'pub');
        if($result['code'] != 100) {
            return $result;
        }
        $row = &$result['data']['row'];
        $form_obj = t($row['tablename']);
        //必填检测
        if(!$id){
            define('ATTACHCHECK',true);
        }
        $result = $this->checkRequiredFields($formid);
        if($result['code'] != 100) {
            return $result;
        }
        //唯一检测
        $result = $this->checkUniqueFields($formid,$id);
        if($result['code'] != 100) {
            return $result;
        }
        $result = t('customform_fields')->fetchFieldsValue($formid);
        if($result['code'] != 100) {
            return $result;
        }
        $data = $result['data'];
        if($id) {
            $form_obj->update($id, $data);
            $form_obj->lastUpdateTime();
            return getResult(100,'',10013);
        } else {
            $data['ischeck'] = !empty($row['needcheck'])?1:2;
            $data['mid'] = $_M['mid'];
            $data['ip'] = $_M['ip'];
            $data['createtime'] = TIME;
            $id = $form_obj->insert($data,true);
            return getResult(100,'',10014);
        }
    }

    /**
     * 是否唯一检查
     * @param $formid 自定义表单ID
     * @param $id
     */
    private function checkUniqueFields($formid,$id='')
    {
        if(empty($formid)) {
            return getResult(10001);
        }
        $tablename = t('customform')->fetchFields($formid,'tablename');
        if(empty($tablename)){
            return getResult(10320);
        }
        $uniques = t('customform_fields')->fetchList(array('formid' => $formid, 'unique' => 1, 'available' => 1), 'title,fieldname');
        if(empty($uniques)){
            return getResult(100);
        }
        $this->multilevel_change($uniques);
        foreach($uniques as $v) {
            $val = filterString(_gp($v['fieldname']));
            $num = $id && t($tablename)->count(array('id'=>$id,$v['fieldname']=>$val))?1:0;
            $count = t($tablename)->count(array($v['fieldname']=>$val))-$num;
            if($count>0) {
                return getResult(10020, '', array('title' => $val));
            }
        }
        return getResult(100);
    }

    /**
     * 自定义表单数据列表
     * @param unknown_type $post
     */
    public function data_list($post)
    {
        $result = $this->_customform_fields($post['formid']);
        if($result['code']!=100){
            return $result;
        }
        $_data = &$result['data'];
        $_data['fieldlist'] = t('customform_fields')->fetchList(array('formid'=>$post['formid'],'available'=>1,'inlist'=>1),'id,title,fieldname,datatype,rules','','displayorder');
        $fields = array('id','ischeck','createtime');
        $fs = array();
        foreach($_data['fieldlist'] as $k=>$v){
            if($v['datatype']=='imgs'){
                unset($_data['fieldlist'][$k]);
                continue;
            }
            if($v['datatype']=='multilevel' && strpos($v['title'],'#')){
                list($v['title'],) = explode('#',$v['title']);
            }
            $fs[] = $fields[] = $v['fieldname'];
            $_data['fieldlist'][$k] = $v;
        }

        $where = array();
        $list = t('customform_fields')->fetchSearchCondition($post['formid']);
        if(!empty($list['where'])){
            $where = $list['where'];
        }
        if(!empty($post['where'])){
            $where += (array)$post['where'];
        }

        $_data['words'] = '';
        if($fs && !empty($post['words'])){
            $_data['words'] = $post['words'];
            $where[] = db::fbuild('CONCAT('.implode(',',$fs).')',$post['words'],'like');
        }

        $condition = array();
        $condition['where'] = $where;
        $condition['pagesize'] = !empty($post['pagesize'])?$post['pagesize']:30;
        $condition['sc'] = !empty($post['sc'])?$post['sc']:'';
        $condition['order'] = !empty($post['order'])?$post['order']:'';
        if($fields){
            $condition['fields'] = implode(',', $fields);
        }
        $_data['result'] = t($_data['form']['tablename'])->info_list($condition);
        $formdata = new mop_formdata;
        foreach ($_data['result']['list'] as $k=>$v){
            $this->parseData($v);
            $formdata->get_value_from_db($_data['fieldlist'], $v);
            $_data['result']['list'][$k] = $v;
        }
        return $_data;
    }

    /**
     * 获取指定数量的数据
     * @param unknown_type $post
     */
    public function dataLimit($post)
    {
        if (empty($post['formid'])) {
            return getResult(10001);
        }
        $tablename = t('customform')->fetchFields($post['formid'],'tablename');
        if (empty($tablename)) {
            return getResult(10004);
        }

        $limit = !empty($post['limit']) ? $post['limit'] : 10;
        $order = !empty($post['order']) ? $post['order'] : '';
        $sc = !empty($post['sc']) ? $post['sc'] : 'desc';
        $fields = !empty($post['fields']) ? $post['fields'] : '';

        $where = array();
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
            $fieldlist = t('customform_fields')->fetchList(array('formid'=>$post['formid'],'available'=>1,'fieldname'=>explode(',', $fields)),'id,title,fieldname,datatype,rules','','displayorder');
        }else{
            $fieldlist = t('customform_fields')->fetchList(array('formid'=>$post['formid'],'available'=>1),'id,title,fieldname,datatype,rules','','displayorder');
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

        $list = t($tablename)->fetchList($where, implode(',', $fields), $limit, $order, $sc);
        $formdata = new mop_formdata;
        foreach ($list as $k=>$v){
            $this->parseData($v);
            $formdata->get_value_from_db($fieldlist, $v);
            $list[$k] = $v;
        }
        return getResult(100,$list);
    }

    private function parseData(&$data)
    {
        if(!empty($data['createtime'])) {
            $data['_createtime'] = mdate($data['createtime'],'dt');
        }
        if(!empty($data['ischeck'])) {
            $data['_ischeck'] = $data['ischeck']==1?'未审核':'已审核';
        }
    }

    /**
     * 某条自定义表单数据详细
     * @param unknown_type $formid
     * @param unknown_type $id
     */
    public function data_view($formid,$id)
    {
        $tablename = t('customform')->fetchFields($formid,'tablename');
        if(empty($tablename)){
            return array();
        }
        $res = t($tablename)->fetch($id);
        if(empty($res)){
            return array();
        }
        $this->parseData($res);
        //如果开启会员插件
        $res['_mid'] = pluginIsAvailable('member')?t('member')->fetchFields($res['mid'],'username'):'';
        $res['fieldlist'] = t('customform_fields')->fetchList(array('formid'=>$formid,'available'=>1),'title,fieldname,datatype,units,rules','','displayorder');
        foreach($res['fieldlist'] as $k=>$v){
            if(!empty($v['rules'])) {
                $v['_rules'] = unserialize($v['rules']);
            }
            if($v['datatype']=='multilevel' && strpos($v['title'],'#')){
                list($v['title'],) = explode('#',$v['title']);
            }
            $res['fieldlist'][$k] = $v;
        }
        $formdata = new mop_formdata;
        $formdata->get_value_from_db($res['fieldlist'], $res);
        return $res;
    }

    public function data_check($formid,$id,$ischeck)
    {
        $tablename = t('customform')->fetchFields($formid,'tablename');
        if(empty($tablename)){
            return false;
        }
        return t($tablename)->update($id,array('ischeck'=>$ischeck));
    }

    /**
     * 删除某条数据
     * @param unknown_type $formid
     * @param unknown_type $id
     */
    public function data_del($formid,$id)
    {
        $tablename = t('customform')->fetchFields($formid,'tablename');
        if(empty($tablename)){
            return false;
        }
        $res = t($tablename)->fetch($id);
        if(empty($res)){
            return false;
        }
        $fieldlist = t('customform_fields')->fetchList(array('formid'=>$formid,'available'=>1,'datatype'=>array('img','imgs','media','addon')));
        foreach ($fieldlist as $v){
            if($v['datatype']=='imgs') {
                foreach(unserialize($res[$v['fieldname']]) as $p) {
                    $this->_del_image($p['img']);
                }
            }else{
                $this->_del_image($res[$v['fieldname']]);
            }
        }
        return t($tablename)->delete($id);
    }

    /**
     * 数据导出
     * @param unknown_type $formid
     * @param unknown_type $id
     */
    public function data_export($formid)
    {
        $tablename = t('customform')->fetchFields($formid,'tablename');
        if(empty($tablename)){
            return '';
        }
        $fieldlist = t('customform_fields')->fetchList(array('formid'=>$formid,'available'=>1),'title,fieldname,datatype,units,rules','','displayorder');
        $fields = array('id','ip','createtime','mid','ischeck');
        foreach ($fieldlist as $v) {
            $fields[] = $v['fieldname'];
        }
        $list = t($tablename)->fetchList('', implode(',', $fields));
        foreach ($list as $k=>$v) {
            $this->parseData($v);
            $list[$k] = $v;
        }
        return $this->export($formid,$fieldlist,$list);
    }

    /**
     * 自定义表单中数据搜索的字段列表
     * @param $formid
     */
    public function search_fields($formid)
    {
        global $_M;
        static $list = array();
        if (empty($formid)){
            return array();
        }
        if($list){
            return $list;
        }
        $list = t('customform_fields')->fetchSearchCondition($formid);
        foreach ($list['fields'] as $k=>$v){
            $rules = $v['rules'];
            $v['rules'] = array();
            foreach ($rules as $key=>$val){
                $arr = array();
                $arr['link'] = pseudoUrl($_M['cururl'].'&'.$v['fieldname'].'='.$key);
                $arr['active'] = $key==_gp($v['fieldname'])?'active':'';
                $arr['value'] = $key;
                $arr['name'] = $val;
                $v['rules'][$key] = $arr;
            }
            $list['fields'][$k] = $v;
        }
        return $list;
    }
    /**
     * 自定义表单中参与排序字段列表标签
     */
    public function orderby_fields($formid,$sc,$order)
    {
        global $_M;
        if (empty($formid)){
            return array();
        }
        $list = t('customform_fields')->getOrderByFields($formid);
        foreach ($list as $k=>$v){
            $url = $_M['cururl'].'&order='.$v['fieldname'].'&sc='.($sc=='asc'?'desc':'asc');
            $v['link'] = pseudoUrl($url);
            $v['order'] = $order;
            $v['sc'] = $sc=='asc'?'up':'down';
            $list[$k] = $v;
        }
        return $list;
    }
}