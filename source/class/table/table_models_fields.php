<?php
/**
 * 内容模型字段管理处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_models_fields extends base_table
{
    protected function parseData(& $data)
    {
        if(isset($data['available'])) {
            $data['_available'] = empty($data['available']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['infront'])) {
            $data['_infront'] = empty($data['infront']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['required'])) {
            $data['_required'] = empty($data['required']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['unique'])) {
            $data['_unique'] = empty($data['unique']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['search'])) {
            $data['_search'] = empty($data['search']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['joinsorting'])) {
            $data['_joinsorting'] = empty($data['joinsorting']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['inlist'])) {
            $data['_inlist'] = empty($data['inlist']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(!empty($data['rules'])) {
            $data['_rules'] = unserialize($data['rules']);
        }
    }

    /**
     * 检测某字段在主表和附表中是否存在（类外部有用到）
     */
    public function fieldCheck($modelid, $fieldname)
    {
        if($this->count(array('modelid' => $modelid, 'fieldname' => $fieldname))) {
            return getResult(10310);
        }
        $sys_fields = t('archives')->tableFields();
        if(!empty($sys_fields[$fieldname])) {
            return getResult(10311);
        }
        return getResult(100);
    }

    /**
     * 删除某字段
     * @param int $id
     * @version 1.0
     */
    public function deleteById($id)
    {
        if(empty($id)) {
            return getResult(10006);
        }
        $row = $this->fetch($id);
        if(empty($row)) {
            return getResult(10004);
        }
        $tablename = t('models')->fetchFields($row['modelid'], 'tablename');
        $formdata = new mop_formdata($tablename);
        $formdata->field_delete($row['fieldname']);
        $this->delete($id);
        return getResult(100,'',10314);
    }

    /**
     * 获取在列表中显示的附加字段
     * @param $modelid 模型ID
     */
    public function getInlistFields($modelid)
    {
        $listfields = array();
        $result = $this->fetchList(array('modelid' => $modelid, 'available' => 1, 'inlist' => 1), 'fieldname');
        if(empty($result)) {
            return '';
        }
        foreach($result as $v) {
            $listfields[] = $v['fieldname'];
        }
        return implode(',', $listfields);
    }

    /**
     * 获取在列表中参与排序的字段
     * @param $modelid 模型ID
     */
    public function getOrderByFields($modelid)
    {
        return $this->fetchList(array('modelid' => $modelid, 'available' => 1, 'joinsorting' => 1), 'title,fieldname','','displayorder');
    }

    /**
     * 必填字段检查
     * @param $modelid 模型ID
     * @version 1.0
     */
    public function checkRequiredFields($modelid)
    {
        if(empty($modelid)) {
            return getResult(10401);
        }
        $requireds = $this->fetchList(array('modelid' => $modelid, 'required' => 1, 'available' => 1), 'datatype,title,fieldname');
        if(empty($requireds)){
            return getResult(100);
        }
        return loadm('archives')->checkRequiredFields($requireds);
    }

    /**
     * 是否唯一检查
     * @param $modelid 模型ID
     * @param $id 文档ID
     */
    public function checkUniqueFields($modelid,$id='')
    {
        if(empty($modelid)) {
            return getResult(10401);
        }
        $tablename = t('models')->fetchFields($modelid,'tablename');
        if(empty($tablename)){
            return getResult(10303);
        }
        $uniques = $this->fetchList(array('modelid' => $modelid, 'unique' => 1, 'available' => 1), 'title,fieldname');
        if(empty($uniques)){
            return getResult(100);
        }
        foreach($uniques as $v) {
            $val = filterString(_gp($v['fieldname']));
            $num = $id && t($tablename)->count(array('aid'=>$id,$v['fieldname']=>$val))?1:0;
            $count = t($tablename)->count(array($v['fieldname']=>$val)) - $num;
            if($count) {
                return getResult(10020, '', array('title' => $val));
            }
        }
        return getResult(100);
    }

    /**
     * 生成筛选条件
     * @param $modelid 模型ID
     */
    public function fetchSearchCondition($modelid)
    {
        global $_M;
        static $result = array();
        if(empty($modelid)){
            return array();
        }
        if(empty($result)){
            $fields = $this->fetchList(array('modelid' => $modelid, 'available' => 1, 'search' => 1), 'fieldname,datatype,rules,title', '', 'displayorder');
            $formdata = new mop_formdata();
            $result = $formdata->fetchSearchCondition($fields);
        }
        return $result;
    }

    /**
     * 生成表单结构
     * @param int $modelid 模型ID
     * @param array $row 默认值
     */
    public function getFormFields($modelid, $row = '')
    {
        global $_M;
        $condi = array('modelid' => $modelid, 'available' => 1, 'infront' => 1);
        if(!empty($_M['admin']['id'])) {
            unset($condi['infront']);
        }
        $fields = (array) $this->fetchList($condi, '*', '', 'displayorder');
        $formdata = new mop_formdata();
        return $formdata->form_fields_html($fields,$row);
    }

    /**
     * 获取表单自定义字段数据
     */
    public function fetchFieldsValue($modelid, $row = '', & $data)
    {
        global $_M;
        $fields = $this->fetchList(array('modelid' => $modelid, 'available' => 1));
        $formdata = new mop_formdata();
        return $formdata->fetchFieldsValue($fields,$row,$data);
    }
}