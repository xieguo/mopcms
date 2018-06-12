<?php
/**
 * 后台模型管理模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_models extends base_model
{
    /**
     * 模型修改页面
     */
    public function models_edit($modelid)
    {
        global $_M;
        if(empty($modelid)){
            return getResult(10006);
        }
        $_data = array();
        $_data['modelid'] = $modelid;
        $_data['row'] = t('models')->fetch($modelid);
        if(empty($_data['row'])){
            return getResult(10303);
        }
        return getResult(100,$_data);
    }
    /**
     * 模型修改操作
     */
    public function models_save($post)
    {
        global $_M;
        $data = array();
        $data['modelname'] = $post['modelname'];
        if(empty($data['modelname'])) {
            return getResult(10301);
        }
        $data['byname'] = $post['byname'];
        $data['enable'] = $post['enable'];
        if(pluginIsAvailable('member')) {
            $data['allowpub'] = $post['allowpub'];
            $data['docstatus'] = $post['docstatus'];
            $data['need_thumb'] = $post['need_thumb'];
            $data['need_diysort'] = $post['need_diysort'];
        }
        $data['titlename'] = $post['titlename'];
        $data['unique'] = $post['unique'];
        $data['columnid'] = $post['columnid'];
        if($post['id']!=-1 && empty($data['columnid']) && pluginIsAvailable('member')) {
            return getResult(10302);
        }
        $data['intro'] = $post['intro'];
        if(!empty($post['id'])) {
            t('models')->update($post['id'], $data);
            t('models')->lastUpdateTime();
            return getResult(100, '',10304);
        } else {
            $data['tablename'] = $post['tablename'];
            if(empty($data['tablename'])) {
                return getResult(10305);
            }
            if(t('models')->count(array('tablename' => $data['tablename']))) {
                return getResult(10306);
            }
            //创建附加表
            if(!t('models')->createTable($data['tablename'])) {
                return getResult(10307);
            }
            $id = t('models')->insert($data, true);
            return getResult(100, $id,10308);
        }
    }

    /**
     * 模型字段列表页面
     */
    public function models_fields_list($modelid)
    {
        $result = $this->_models_fields($modelid);
        if($result['code']!=100){
            return $result;
        }
        $_data = $result['data'];
        $_data['modelid'] = $modelid;
        $_data['list'] = t('models_fields')->fetchListLoop(array('modelid'=>$modelid),'','','displayorder');
        return getResult(100,$_data);
    }

    /**
     * 字段修改
     * @param $modelid 模型ID
     * @param $id 字段ID
     */
    public function models_fields_edit($modelid,$id)
    {
        if(empty($id)){
            return getResult(10006);
        }
        $result = $this->_models_fields($modelid);
        if($result['code']!=100){
            return $result;
        }
        $_data = $result['data'];
        $_data['modelid'] = $modelid;
        $_data['id'] = $id;
        $row = t('models_fields')->fetch($id);
        if(empty($row)){
            return getResult(10004);
        }
        $_data['row'] = $row;
        return getResult(100,$_data);
    }
    /**
     * 模型字段添加修改
     * @param unknown_type $post
     */
    public function models_fields_save($post)
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
            $row = t('models_fields')->fetch($post['id']);
            if(empty($row)) {
                return getResult(10004);
            }
            t('models_fields')->update($post['id'], $data);
            t('models_fields')->lastUpdateTime();
            $this->_field_update($row['modelid'],$row['fieldname'],$data);
            return getResult(100,'',10312);
        } else {
            $data['modelid'] = $post['modelid'];
            $data['fieldname'] = $post['fieldname'];
            $result = t('models_fields')->fieldCheck($data['modelid'], $data['fieldname']);
            if($result['code'] != 100) {
                return $result;
            }
            t('models_fields')->insert($data);
            $this->_field_update($data['modelid'],$data['fieldname'],$data);
            return getResult(100,'',10313);
        }
    }
    /**
     * 数据表处理
     * @param $modelid 模型ID
     * @param $fieldname 字段名
     * @param $data 数据
     */
    private function _field_update($modelid,$fieldname,$data)
    {
        $tablename = t('models')->fetchFields($modelid, 'tablename');
        t('models')->createTable($tablename);
        $formdata = new mop_formdata($tablename);
        $formdata->field_update($fieldname, $data);
    }

    /**
     * 字段添加
     * @param $modelid 模型ID
     */
    public function models_fields_add($modelid)
    {
        $result = $this->_models_fields($modelid);
        if($result['code']!=100){
            return $result;
        }
        $_data = $result['data'];
        $_data['modelid'] = $modelid;
        return getResult(100,$_data);
    }

    private function _models_fields($modelid)
    {
        if(empty($modelid)){
            return getResult(10001);
        }
        $_data = array();
        $_data['model'] = t('models')->fetch($modelid);
        if(empty($_data['model'])){
            return getResult(10004);
        }
        return getResult(100,$_data);
    }
}